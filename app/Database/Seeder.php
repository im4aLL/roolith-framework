<?php
namespace App\Database;

use App\Core\DatabaseFactory;
use App\Core\Log;
use Roolith\Store\Interfaces\DatabaseInterface;
use Throwable;

/**
 * Minimal file-based seeder runner.
 *
 * Tracks applied seeders in a `seeds` table so seed data never
 * duplicates between deploys. Seeder files live in database/seeders
 * and declare a class implementing SeederInterface. Run via
 * `php roolith seed` (pending only), `php roolith seed:status`,
 * `php roolith seed:create Name`, and `php roolith seed:run [Name]`
 * (plus `seeder:create` and `seeder:run` upstream aliases).
 */
final class Seeder
{
    /**
     * Default seeders directory relative to APP_ROOT.
     *
     * @var string
     */
    public const DEFAULT_DIR = 'database/seeders';

    /**
     * Tracking table holding applied seed names.
     *
     * @var string
     */
    public const TABLE = 'seeds';

    /**
     * Seeders directory absolute path.
     *
     * @var string
     */
    private string $dir;

    /**
     * Database connection for seed and status queries.
     *
     * @var DatabaseInterface
     */
    private DatabaseInterface $db;

    /**
     * Create a seeder for a directory and connection.
     *
     * @param string|null $dir Absolute seeders dir (defaults to APP_ROOT/database/seeders).
     * @param DatabaseInterface|null $db Database connection (defaults to the shared factory instance).
     */
    public function __construct(?string $dir = null, ?DatabaseInterface $db = null)
    {
        $base = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);
        $this->dir = $dir ?? rtrim($base, "/\\") . '/' . self::DEFAULT_DIR;
        $this->db = $db ?? DatabaseFactory::getInstance();
    }

    /**
     * Get the seeders directory.
     *
     * @return string Absolute seeders directory.
     */
    public function directory(): string
    {
        return $this->dir;
    }

    /**
     * Ensure the tracking table exists.
     *
     * Creates `seeds` (seed VARCHAR primary, batch INT,
     * seeded_at TIMESTAMP) when missing. Never throws for an
     * existing table.
     *
     * @return void
     */
    public function ensureTable(): void
    {
        $this->db->execute(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (' .
            '`seed` VARCHAR(255) NOT NULL PRIMARY KEY, ' .
            '`batch` INT NOT NULL DEFAULT 1, ' .
            '`seeded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ');'
        );
    }

    /**
     * List seeder files sorted by name.
     *
     * Only *.php files are considered; the list is sorted so timestamp
     * prefixes run in order. Missing dir yields an empty list.
     *
     * @return array<int, string> Seeder base names without .php.
     */
    public function files(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }

        $entries = scandir($this->dir);

        if (!is_array($entries)) {
            return [];
        }

        $names = [];

        foreach ($entries as $entry) {
            if (!str_ends_with($entry, '.php')) {
                continue;
            }

            $names[] = substr($entry, 0, -4);
        }

        sort($names);

        return $names;
    }

    /**
     * List applied seed names.
     *
     * Fails loudly when the tracking table cannot be read: logs via
     * Log::error() then rethrows so run() never mistakes a read
     * failure for an empty success.
     *
     * @return array<int, string> Applied seed names.
     * @throws \RuntimeException When the applied list cannot be read.
     */
    public function applied(): array
    {
        $this->ensureTable();

        try {
            $rows = $this->db->table(self::TABLE)->select(['field' => 'seed'])->get();
        } catch (Throwable $e) {
            Log::error('seeder applied read failed', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500)]);
            throw new \RuntimeException('Unable to read applied seeds: ' . $e->getMessage(), 0, $e);
        }

        $names = [];

        foreach ($rows as $row) {
            if (is_object($row) && isset($row->seed) && is_string($row->seed)) {
                $names[] = $row->seed;
            } elseif (is_array($row) && isset($row['seed']) && is_string($row['seed'])) {
                $names[] = $row['seed'];
            }
        }

        sort($names);

        return $names;
    }

    /**
     * Show pending plus applied status without running anything.
     *
     * @return array<string, array<int, string>> Map with pending and applied keys.
     * @throws \RuntimeException When the applied list cannot be read.
     */
    public function status(): array
    {
        $all = $this->files();
        $done = $this->applied();
        $doneSet = array_fill_keys($done, true);
        $pending = [];

        foreach ($all as $name) {
            if (!isset($doneSet[$name])) {
                $pending[] = $name;
            }
        }

        return ['applied' => $done, 'pending' => $pending];
    }

    /**
     * Run pending seeders in filename order, or one named seeder.
     *
     * Each seeder runs inside the injected connection transaction()
     * so a failing run() rolls back its own statements; the tracking
     * row is written only on success. Returns the names that ran.
     * An already-applied single name is a no-op returning [].
     *
     * @param string|null $name Optional single seeder to run.
     * @return array<int, string> Seeded names in run order.
     * @throws \InvalidArgumentException When the name is unknown or unsafe.
     * @throws \RuntimeException When a seeder file, class, or tracking read fails.
     */
    public function run(?string $name = null): array
    {
        if ($name !== null) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new \InvalidArgumentException("Invalid seed name '{$name}' (expected letters, digits, underscores only).");
            }

            $all = $this->files();

            if (!in_array($name, $all, true)) {
                throw new \InvalidArgumentException("Unknown seed '{$name}'.");
            }

            $done = $this->applied();

            if (in_array($name, $done, true)) {
                return [];
            }

            $this->runOne($name);

            return [$name];
        }

        $pending = $this->status()['pending'];
        $ran = [];

        foreach ($pending as $pendingName) {
            $this->runOne($pendingName);
            $ran[] = $pendingName;
        }

        return $ran;
    }

    /**
     * Scaffold a new seeder file.
     *
     * Creates database/seeders/<timestamp>_<rand>_<Slug>.php with a
     * run() skeleton implementing SeederInterface. The directory is
     * created when missing. Throws when the name is empty or the
     * directory or file cannot be written; retries with a random
     * suffix until the name is unique so concurrent creates never
     * collide.
     *
     * @param string $name Human name like add_users.
     * @return string Created seeder base name without .php.
     * @throws \InvalidArgumentException When the name is empty after sanitizing.
     * @throws \RuntimeException When the directory or file cannot be written.
     */
    public function create(string $name): string
    {
        $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name) ?? '', '_');

        if ($slug === '') {
            throw new \InvalidArgumentException('Seeder name must not be empty.');
        }

        if (!is_dir($this->dir)) {
            $made = mkdir($this->dir, 0775, true);

            if ($made === false && !is_dir($this->dir)) {
                throw new \RuntimeException("Unable to create seeders directory: {$this->dir}");
            }
        }

        $base = '';
        $attempts = 0;

        do {
            $suffix = $attempts === 0 ? '' : '_' . $attempts;

            try {
                $rand = bin2hex(random_bytes(3));
            } catch (Throwable $e) {
                throw new \RuntimeException('Unable to generate seeder suffix: ' . $e->getMessage(), 0, $e);
            }

            $candidate = time() . '_' . $rand . '_' . $slug . $suffix;
            $attempts++;

            if ($attempts > 100) {
                throw new \RuntimeException("Unable to pick a unique seeder name for '{$slug}'.");
            }

            $base = $candidate;
        } while (file_exists($this->dir . '/' . $base . '.php'));

        $class = $this->classNameFor($base);

        $template = '<?php' . PHP_EOL . PHP_EOL
            . 'use App\Database\SeederInterface;' . PHP_EOL
            . 'use Roolith\Store\Interfaces\DatabaseInterface;' . PHP_EOL . PHP_EOL
            . 'class ' . $class . ' implements SeederInterface' . PHP_EOL
            . '{' . PHP_EOL
            . '    /**' . PHP_EOL
            . '     * Run the seeder.' . PHP_EOL
            . '     *' . PHP_EOL
            . '     * @param DatabaseInterface $db Shared database connection.' . PHP_EOL
            . '     * @return void' . PHP_EOL
            . '     */' . PHP_EOL
            . '    public function run(DatabaseInterface $db): void' . PHP_EOL
            . '    {' . PHP_EOL
            . '    }' . PHP_EOL
            . '}' . PHP_EOL;

        $written = file_put_contents($this->dir . '/' . $base . '.php', $template);

        if ($written === false) {
            throw new \RuntimeException("Unable to write seeder file: {$this->dir}/{$base}.php");
        }

        return $base;
    }

    /**
     * Run one pending seeder by name.
     *
     * Loads the file, instantiates the class, runs run() inside the
     * injected connection transaction, then records the tracking row.
     * Throws when the file or class is missing or run() fails.
     *
     * @param string $name Seeder base name without .php.
     * @return void
     * @throws \InvalidArgumentException When the name contains unsafe characters.
     * @throws \RuntimeException When the file or class is missing.
     */
    private function runOne(string $name): void
    {
        $this->ensureTable();
        $instance = $this->load($name);
        $batch = $this->nextBatch();

        $this->db->transaction(function (DatabaseInterface $db) use ($instance): void {
            $instance->run($db);
        });

        $this->remember($name, $batch);
    }

    /**
     * Load a seeder file and instantiate its class.
     *
     * The file must declare a class matching the filename suffix
     * after the timestamp prefix and implementing SeederInterface.
     * The name is restricted to letters, digits, and underscores so
     * directory traversal (../) or extension tricks never reach the
     * filesystem.
     *
     * @param string $name Seeder base name without .php.
     * @return SeederInterface Seeder instance.
     * @throws \InvalidArgumentException When the name contains unsafe characters.
     * @throws \RuntimeException When the file or class is missing.
     */
    private function load(string $name): SeederInterface
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid seed name '{$name}' (expected letters, digits, underscores only).");
        }

        $file = rtrim($this->dir, "/\\") . '/' . $name . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("Seeder file missing: {$file}");
        }

        require_once $file;

        $class = $this->classNameFor($name);

        if (!class_exists($class)) {
            throw new \RuntimeException("Seeder class '{$class}' not found in {$file}.");
        }

        $instance = new $class();

        if (!$instance instanceof SeederInterface) {
            throw new \RuntimeException("Seeder class '{$class}' must implement App\\Database\\SeederInterface.");
        }

        return $instance;
    }

    /**
     * Derive the class name from a seeder base name.
     *
     * Strips a leading timestamp prefix (digits plus underscore),
     * splits on underscores, and StudlyCases the parts. When the
     * result would start with a digit (hex random prefix), it is
     * prefixed with Seeder so the file always declares a valid PHP
     * class name.
     *
     * @param string $name Seeder base name.
     * @return string Class name.
     */
    private function classNameFor(string $name): string
    {
        $stripped = preg_replace('/^\d+_/', '', $name) ?? $name;
        $parts = explode('_', (string) $stripped);
        $class = '';

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part !== '') {
                $class .= ucfirst(strtolower($part));
            }
        }

        if ($class === '') {
            return 'Seeder';
        }

        if (!preg_match('/^[A-Za-z]/', $class)) {
            $class = 'Seeder' . $class;
        }

        return $class;
    }

    /**
     * Record a seeder as applied.
     *
     * @param string $name Seeder base name.
     * @param int $batch Batch number.
     * @return void
     */
    private function remember(string $name, int $batch): void
    {
        $this->db->table(self::TABLE)->insert(['seed' => $name, 'batch' => $batch]);
    }

    /**
     * Get the next batch number.
     *
     * @return int One plus the current max batch, or 1 when empty.
     * @throws \RuntimeException When the batch list cannot be read.
     */
    private function nextBatch(): int
    {
        $latest = $this->latestBatch();

        return $latest === null ? 1 : $latest + 1;
    }

    /**
     * Get the latest applied batch number.
     *
     * Fails loudly when the tracking table cannot be read: logs via
     * Log::error() then rethrows so run() never mistakes a read
     * failure for an empty (first batch) result.
     *
     * @return int|null Latest batch or null when none applied.
     * @throws \RuntimeException When the batch list cannot be read.
     */
    private function latestBatch(): ?int
    {
        try {
            $rows = $this->db->table(self::TABLE)->select(['field' => 'batch'])->get();
        } catch (Throwable $e) {
            Log::error('seeder latest batch read failed', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500)]);
            throw new \RuntimeException('Unable to read latest seed batch: ' . $e->getMessage(), 0, $e);
        }

        $max = null;

        foreach ($rows as $row) {
            $value = is_object($row) ? ($row->batch ?? null) : (is_array($row) ? ($row['batch'] ?? null) : null);

            if (is_numeric($value)) {
                $intValue = (int) $value;

                if ($max === null || $intValue > $max) {
                    $max = $intValue;
                }
            }
        }

        return $max;
    }
}
