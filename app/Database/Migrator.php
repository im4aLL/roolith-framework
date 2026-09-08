<?php
namespace App\Database;

use App\Core\DatabaseFactory;
use App\Core\Log;
use Roolith\Store\Interfaces\DatabaseInterface;
use Throwable;

/**
 * Minimal file-based migration runner.
 *
 * Tracks applied migrations in a `migrations` table so schema never
 * drifts between deploys. Migration files live in database/migrations
 * and return a class implementing MigrationInterface. Run via
 * `php roolith migrate` (pending only), `php roolith migrate:status`,
 * `php roolith migrate:create Name`, and `php roolith migrate:rollback`.
 *
 * Model-to-table contract: one model class maps to one table via
 * protected string $table plus $primaryColumn (default id). The
 * migrator creates those tables; the model reads and writes them.
 */
final class Migrator
{
    /**
     * Default migrations directory relative to APP_ROOT.
     *
     * @var string
     */
    public const DEFAULT_DIR = 'database/migrations';

    /**
     * Tracking table holding applied migration names.
     *
     * @var string
     */
    public const TABLE = 'migrations';

    /**
     * Migrations directory absolute path.
     *
     * @var string
     */
    private string $dir;

    /**
     * Database connection for migrate and status queries.
     *
     * @var DatabaseInterface
     */
    private DatabaseInterface $db;

    /**
     * Create a migrator for a directory and connection.
     *
     * @param string|null $dir Absolute migrations dir (defaults to APP_ROOT/database/migrations).
     * @param DatabaseInterface|null $db Database connection (defaults to the shared factory instance).
     */
    public function __construct(?string $dir = null, ?DatabaseInterface $db = null)
    {
        $base = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);
        $this->dir = $dir ?? rtrim($base, "/\\") . '/' . self::DEFAULT_DIR;
        $this->db = $db ?? DatabaseFactory::getInstance();
    }

    /**
     * Get the migrations directory.
     *
     * @return string Absolute migrations directory.
     */
    public function directory(): string
    {
        return $this->dir;
    }

    /**
     * Ensure the tracking table exists.
     *
     * Creates `migrations` (migration VARCHAR primary, batch INT,
     * migrated_at TIMESTAMP) when missing. Never throws for an
     * existing table.
     *
     * @return void
     */
    public function ensureTable(): void
    {
        $this->db->execute(
            'CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (' .
            '`migration` VARCHAR(255) NOT NULL PRIMARY KEY, ' .
            '`batch` INT NOT NULL DEFAULT 1, ' .
            '`migrated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ');'
        );
    }

    /**
     * List migration files sorted by name.
     *
     * Only *.php files are considered; the list is sorted so timestamp
     * prefixes run in order. Missing dir yields an empty list.
     *
     * @return array<int, string> Migration base names without .php.
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
     * List applied migration names.
     *
     * Fails loudly when the tracking table cannot be read: logs via
     * Log::error() then rethrows so run() never mistakes a read
     * failure for an empty success.
     *
     * @return array<int, string> Applied migration names.
     * @throws \RuntimeException When the applied list cannot be read.
     */
    public function applied(): array
    {
        $this->ensureTable();

        try {
            $rows = $this->db->table(self::TABLE)->select(['field' => 'migration'])->get();
        } catch (Throwable $e) {
            Log::error('migrator applied read failed', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500)]);
            throw new \RuntimeException('Unable to read applied migrations: ' . $e->getMessage(), 0, $e);
        }

        $names = [];

        foreach ($rows as $row) {
            if (is_object($row) && isset($row->migration) && is_string($row->migration)) {
                $names[] = $row->migration;
            } elseif (is_array($row) && isset($row['migration']) && is_string($row['migration'])) {
                $names[] = $row['migration'];
            }
        }

        sort($names);

        return $names;
    }

    /**
     * Show pending plus applied status without running anything.
     *
     * @return array<string, array<int, string>> Map with pending and applied keys.
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
     * Run all pending migrations in filename order.
     *
     * Each migration runs inside the injected connection transaction()
     * so a failing up() rolls back its own statements; the tracking row
     * is written only on success. Returns the names that ran.
     *
     * @return array<int, string> Migrated names in run order.
     */
    public function run(): array
    {
        $pending = $this->status()['pending'];
        $ran = [];

        foreach ($pending as $name) {
            $this->runOne($name);
            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Roll back the last batch (or one named migration).
     *
     * When $name is null the most recent batch runs down() in reverse
     * order; otherwise only that migration runs down(). Returns the
     * names that rolled back.
     *
     * @param string|null $name Optional single migration to roll back.
     * @return array<int, string> Rolled-back names.
     */
    public function rollback(?string $name = null): array
    {
        $this->ensureTable();

        if ($name !== null) {
            $this->runDown($name);
            $this->forget($name);

            return [$name];
        }

        $batch = $this->latestBatch();

        if ($batch === null) {
            return [];
        }

        $targets = $this->migrationsInBatch($batch);
        rsort($targets);
        $done = [];

        foreach ($targets as $target) {
            $this->runDown($target);
            $this->forget($target);
            $done[] = $target;
        }

        return $done;
    }

    /**
     * Scaffold a new migration file.
     *
     * Creates database/migrations/<timestamp>_<Slug>.php with an
     * up() plus down() skeleton implementing MigrationInterface.
     * The directory is created when missing. Throws when the directory
     * or file cannot be written; retries with a random suffix until the
     * name is unique so concurrent creates never collide.
     *
     * @param string $name Human name like create_users_table.
     * @return string Created migration base name without .php.
     * @throws \RuntimeException When the directory or file cannot be written.
     */
    public function create(string $name): string
    {
        $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name) ?? '', '_');

        if ($slug === '') {
            $slug = 'migration';
        }

        if (!is_dir($this->dir)) {
            $made = mkdir($this->dir, 0775, true);

            if ($made === false && !is_dir($this->dir)) {
                throw new \RuntimeException("Unable to create migrations directory: {$this->dir}");
            }
        }

        $base = '';
        $attempts = 0;

        do {
            $suffix = $attempts === 0 ? '' : '_' . $attempts;

            try {
                $rand = bin2hex(random_bytes(3));
            } catch (Throwable $e) {
                throw new \RuntimeException('Unable to generate migration suffix: ' . $e->getMessage(), 0, $e);
            }

            $candidate = time() . '_' . $rand . '_' . $slug . $suffix;
            $attempts++;

            if ($attempts > 100) {
                throw new \RuntimeException("Unable to pick a unique migration name for '{$slug}'.");
            }

            $base = $candidate;
        } while (file_exists($this->dir . '/' . $base . '.php'));

        $class = $this->classNameFor($base);

        $template = '<?php' . PHP_EOL . PHP_EOL
            . 'use App\Database\MigrationInterface;' . PHP_EOL
            . 'use Roolith\Store\Interfaces\DatabaseInterface;' . PHP_EOL . PHP_EOL
            . 'class ' . $class . ' implements MigrationInterface' . PHP_EOL
            . '{' . PHP_EOL
            . '    /**' . PHP_EOL
            . '     * Apply the migration.' . PHP_EOL
            . '     *' . PHP_EOL
            . '     * @param DatabaseInterface $db Shared database connection.' . PHP_EOL
            . '     * @return void' . PHP_EOL
            . '     */' . PHP_EOL
            . '    public function up(DatabaseInterface $db): void' . PHP_EOL
            . '    {' . PHP_EOL
            . '    }' . PHP_EOL . PHP_EOL
            . '    /**' . PHP_EOL
            . '     * Revert the migration.' . PHP_EOL
            . '     *' . PHP_EOL
            . '     * @param DatabaseInterface $db Shared database connection.' . PHP_EOL
            . '     * @return void' . PHP_EOL
            . '     */' . PHP_EOL
            . '    public function down(DatabaseInterface $db): void' . PHP_EOL
            . '    {' . PHP_EOL
            . '    }' . PHP_EOL
            . '}' . PHP_EOL;

        $written = file_put_contents($this->dir . '/' . $base . '.php', $template);

        if ($written === false) {
            throw new \RuntimeException("Unable to write migration file: {$this->dir}/{$base}.php");
        }

        return $base;
    }

    /**
     * Run one pending migration by name.
     *
     * Loads the file, instantiates the class, runs up() inside the
     * injected connection transaction, then records the tracking row.
     * Throws when the file or class is missing or up() fails.
     *
     * @param string $name Migration base name without .php.
     * @return void
     */
    private function runOne(string $name): void
    {
        $this->ensureTable();
        $instance = $this->load($name);
        $batch = $this->nextBatch();

        $this->db->transaction(function (DatabaseInterface $db) use ($instance): void {
            $instance->up($db);
        });

        $this->remember($name, $batch);
    }

    /**
     * Run one migration down() inside a transaction.
     *
     * Uses the injected connection so tests can isolate with a double.
     *
     * @param string $name Migration base name without .php.
     * @return void
     */
    private function runDown(string $name): void
    {
        $instance = $this->load($name);

        $this->db->transaction(function (DatabaseInterface $db) use ($instance): void {
            $instance->down($db);
        });
    }

    /**
     * Load a migration file and instantiate its class.
     *
     * The file must declare a class matching the filename suffix
     * after the timestamp prefix and implementing MigrationInterface.
     * The name is restricted to letters, digits, and underscores so
     * directory traversal (../) or extension tricks never reach the
     * filesystem.
     *
     * @param string $name Migration base name without .php.
     * @return MigrationInterface Migration instance.
     * @throws \InvalidArgumentException When the name contains unsafe characters.
     * @throws \RuntimeException When the file or class is missing.
     */
    private function load(string $name): MigrationInterface
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid migration name '{$name}' (expected letters, digits, underscores only).");
        }

        $file = rtrim($this->dir, "/\\") . '/' . $name . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("Migration file missing: {$file}");
        }

        require_once $file;

        $class = $this->classNameFor($name);

        if (!class_exists($class)) {
            throw new \RuntimeException("Migration class '{$class}' not found in {$file}.");
        }

        $instance = new $class();

        if (!$instance instanceof MigrationInterface) {
            throw new \RuntimeException("Migration class '{$class}' must implement App\\Database\\MigrationInterface.");
        }

        return $instance;
    }

    /**
     * Derive the class name from a migration base name.
     *
     * Strips a leading timestamp prefix (digits plus underscore),
     * splits on underscores, and StudlyCases the parts.
     *
     * @param string $name Migration base name.
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

        return $class === '' ? 'Migration' : $class;
    }

    /**
     * Record a migration as applied.
     *
     * @param string $name Migration base name.
     * @param int $batch Batch number.
     * @return void
     */
    private function remember(string $name, int $batch): void
    {
        $this->db->table(self::TABLE)->insert(['migration' => $name, 'batch' => $batch]);
    }

    /**
     * Forget a rolled-back migration.
     *
     * @param string $name Migration base name.
     * @return void
     */
    private function forget(string $name): void
    {
        $this->db->table(self::TABLE)->delete(['migration' => $name]);
    }

    /**
     * Get the next batch number.
     *
     * @return int One plus the current max batch, or 1 when empty.
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
     * Log::error() then rethrows so rollback() never mistakes a read
     * failure for an empty (nothing to roll back) result.
     *
     * @return int|null Latest batch or null when none applied.
     * @throws \RuntimeException When the batch list cannot be read.
     */
    private function latestBatch(): ?int
    {
        try {
            $rows = $this->db->table(self::TABLE)->select(['field' => 'batch'])->get();
        } catch (Throwable $e) {
            Log::error('migrator latest batch read failed', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500)]);
            throw new \RuntimeException('Unable to read latest migration batch: ' . $e->getMessage(), 0, $e);
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

    /**
     * List migration names in one batch.
     *
     * Fails loudly when the tracking table cannot be read: logs via
     * Log::error() then rethrows so rollback() never silently skips a
     * batch on read failure.
     *
     * @param int $batch Batch number.
     * @return array<int, string> Migration names in the batch.
     * @throws \RuntimeException When the batch list cannot be read.
     */
    private function migrationsInBatch(int $batch): array
    {
        try {
            $rows = $this->db->table(self::TABLE)->select(['field' => ['migration', 'batch']])->get();
        } catch (Throwable $e) {
            Log::error('migrator batch read failed', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500), 'batch' => $batch]);
            throw new \RuntimeException('Unable to read migrations in batch ' . $batch . ': ' . $e->getMessage(), 0, $e);
        }

        $names = [];

        foreach ($rows as $row) {
            $rowBatch = is_object($row) ? ($row->batch ?? null) : (is_array($row) ? ($row['batch'] ?? null) : null);
            $rowName = is_object($row) ? ($row->migration ?? null) : (is_array($row) ? ($row['migration'] ?? null) : null);

            if ((int) $rowBatch === $batch && is_string($rowName)) {
                $names[] = $rowName;
            }
        }

        return $names;
    }
}
