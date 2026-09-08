<?php
namespace Tests;

use App\Database\Seeder;
use PHPUnit\Framework\TestCase;
use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Paginate;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

/**
 * In-memory Database double for seeder tests.
 *
 * Stores seeds table rows as seed => batch, records execute calls,
 * and counts transaction entries. All other methods are inert stubs.
 */
class InMemorySeederDatabase implements DatabaseInterface
{
    /**
     * Stored seed rows keyed by seed name.
     *
     * @var array<string, int>
     */
    private array $seeds = [];

    /**
     * Currently selected table.
     *
     * @var string
     */
    private string $table = '';

    /**
     * Recorded execute queries.
     *
     * @var array<int, string>
     */
    public array $executed = [];

    /**
     * Transaction entry count.
     *
     * @var int
     */
    public int $transactions = 0;

    /**
     * When true, get() throws to simulate a read failure.
     *
     * @var bool
     */
    public bool $failSelect = false;

    /**
     * Establish database connection.
     *
     * @param mixed $config Connection config.
     * @return bool Always true.
     */
    public function connect($config): bool
    {
        return true;
    }

    /**
     * Disconnect from a database.
     *
     * @return bool Always true.
     */
    public function disconnect(): bool
    {
        return true;
    }

    /**
     * Reset all states.
     *
     * @return DatabaseInterface Self.
     */
    public function reset(): DatabaseInterface
    {
        return $this;
    }

    /**
     * Return records for the current table.
     *
     * @return array<int, object> Seed rows as objects.
     */
    public function get(): array
    {
        if ($this->failSelect) {
            throw new \RuntimeException('select failed');
        }

        if ($this->table !== Seeder::TABLE) {
            return [];
        }

        $rows = [];

        foreach ($this->seeds as $seed => $batch) {
            $rows[] = (object) ['seed' => $seed, 'batch' => $batch];
        }

        return $rows;
    }

    /**
     * Return first item of records.
     *
     * @return false|object First row or false.
     */
    public function first(): object|bool
    {
        $rows = $this->get();

        return $rows[0] ?? false;
    }

    /**
     * Get total count of a result.
     *
     * @return int Row count.
     */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Add where condition to an existing query.
     *
     * @param mixed $name Column name.
     * @param mixed $value Bound value or operator.
     * @param mixed $expression Operator or bound value.
     * @return DatabaseInterface Self.
     */
    public function where($name, $value, $expression = '='): DatabaseInterface
    {
        return $this;
    }

    /**
     * Add or where condition to an existing query.
     *
     * @param mixed $name Column name.
     * @param mixed $value Bound value or operator.
     * @param mixed $expression Operator or bound value.
     * @return DatabaseInterface Self.
     */
    public function orWhere($name, $value, $expression = '='): DatabaseInterface
    {
        return $this;
    }

    /**
     * Get data by id.
     *
     * @param mixed $id Record id.
     * @return object|false Always false.
     */
    public function find($id): object|bool
    {
        return false;
    }

    /**
     * Retrieve an array of items.
     *
     * @param mixed $nameArray Columns.
     * @return array<int, mixed> Always empty.
     */
    public function pluck($nameArray): array
    {
        return [];
    }

    /**
     * Set ORDER BY for the next select/get.
     *
     * @param string $column Column name.
     * @param string $direction ASC or DESC.
     * @return DatabaseInterface Self.
     */
    public function orderBy(string $column, string $direction = 'ASC'): DatabaseInterface
    {
        return $this;
    }

    /**
     * Set LIMIT/OFFSET for the next select/get.
     *
     * @param int $limit Row limit.
     * @param int $offset Row offset.
     * @return DatabaseInterface Self.
     */
    public function limit(int $limit, int $offset = 0): DatabaseInterface
    {
        return $this;
    }

    /**
     * Set OFFSET for the next select/get.
     *
     * @param int $offset Row offset.
     * @return DatabaseInterface Self.
     */
    public function offset(int $offset): DatabaseInterface
    {
        return $this;
    }

    /**
     * Pagination stub.
     *
     * @param array<string, mixed> $array Pagination params.
     * @return PaginatorInterface Empty paginator.
     */
    public function paginate(array $array): PaginatorInterface
    {
        return new Paginate($array);
    }

    /**
     * Database raw query stub.
     *
     * @param mixed $string Query string.
     * @param mixed $method Query method.
     * @param mixed $bindings Bound values.
     * @return DatabaseInterface Self.
     */
    public function query($string, $method = null, $bindings = []): DatabaseInterface
    {
        return $this;
    }

    /**
     * Database raw execute.
     *
     * @param string $query Query string.
     * @param array<string, mixed> $bindings Bound values.
     * @return mixed Always true.
     */
    public function execute(string $query, array $bindings = []): mixed
    {
        $this->executed[] = $query;

        return true;
    }

    /**
     * Set table name.
     *
     * @param mixed $name Table name.
     * @return DatabaseInterface Self.
     */
    public function table($name): DatabaseInterface
    {
        $this->table = (string) $name;

        return $this;
    }

    /**
     * Database select query stub.
     *
     * @param mixed $array Select spec.
     * @param mixed $bindings Bound values.
     * @return DatabaseInterface Self.
     */
    public function select($array, $bindings = []): DatabaseInterface
    {
        return $this;
    }

    /**
     * Insert query storing seed rows in memory.
     *
     * @param mixed $array Row data.
     * @param array<int, string> $uniqueArray Unique columns.
     * @return InsertResponse Insert result.
     */
    public function insert($array, array $uniqueArray = []): InsertResponse
    {
        if ($this->table === Seeder::TABLE && is_array($array) && isset($array['seed']) && is_string($array['seed'])) {
            $batch = isset($array['batch']) && is_numeric($array['batch']) ? (int) $array['batch'] : 1;
            $this->seeds[$array['seed']] = $batch;
        }

        return new InsertResponse(['affectedRow' => 1, 'insertedId' => 1]);
    }

    /**
     * Update query stub.
     *
     * @param mixed $array Row data.
     * @param array<string, mixed> $whereArray Where clause.
     * @param array<int, string> $uniqueArray Unique columns.
     * @return UpdateResponse Update result.
     */
    public function update($array, array $whereArray, array $uniqueArray = []): UpdateResponse
    {
        return new UpdateResponse(['affectedRow' => 0]);
    }

    /**
     * Delete query stub.
     *
     * @param mixed $whereArray Where clause.
     * @return DeleteResponse Delete result.
     */
    public function delete($whereArray): DeleteResponse
    {
        return new DeleteResponse(['affectedRow' => 0]);
    }

    /**
     * Turn on debug mode.
     *
     * @param bool $mode Debug flag.
     * @return DatabaseInterface Self.
     */
    public function debugMode(bool $mode = true): DatabaseInterface
    {
        return $this;
    }

    /**
     * Whether a transaction is active.
     *
     * @return bool Always false.
     */
    public function inTransaction(): bool
    {
        return false;
    }

    /**
     * Begin transaction stub.
     *
     * @return bool Always true.
     */
    public function beginTransaction(): bool
    {
        return true;
    }

    /**
     * Commit transaction stub.
     *
     * @return bool Always true.
     */
    public function commit(): bool
    {
        return true;
    }

    /**
     * Roll back transaction stub.
     *
     * @return bool Always true.
     */
    public function rollBack(): bool
    {
        return true;
    }

    /**
     * Run callback inside a transaction.
     *
     * @param callable $callback Work receiving this instance.
     * @return mixed Callback return value.
     */
    public function transaction(callable $callback): mixed
    {
        $this->transactions++;

        return $callback($this);
    }

    /**
     * Get collected debug queries.
     *
     * @return array<int, array{query:string, bindings:mixed}> Always empty.
     */
    public function getDebugLog(): array
    {
        return [];
    }

    /**
     * Clear collected debug queries.
     *
     * @return DatabaseInterface Self.
     */
    public function clearDebugLog(): DatabaseInterface
    {
        return $this;
    }
}

/**
 * Covers the internal file-based seeder runner.
 *
 * Mirrors the Phase5 migrator tests: create plus files plus status
 * plus run with an in-memory double or temp dir, slug guard, and
 * single-run behavior.
 */
class SeederTest extends TestCase
{
    /**
     * Ensure framework constants exist.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', APP_ROOT . '/views');
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }
    }

    /**
     * Create an isolated temp directory.
     *
     * @return string Temp directory path.
     */
    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/roolith-seeder-' . uniqid('', true);
        mkdir($dir, 0775, true);

        return $dir;
    }

    /**
     * Remove a temp directory and its files.
     *
     * @param string $dir Temp directory path.
     * @return void
     */
    private function removeTempDir(string $dir): void
    {
        $files = glob($dir . '/*') ?: [];

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    /**
     * Seeder create plus files must work on an empty dir.
     *
     * @return void
     */
    public function testCreateAndFiles(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = new Seeder($dir, new InMemorySeederDatabase());
            $created = $seeder->create('add_users');

            $this->assertFileExists($dir . '/' . $created . '.php');
            $this->assertContains($created, $seeder->files());

            $contents = (string) file_get_contents($dir . '/' . $created . '.php');
            $this->assertStringContainsString('implements SeederInterface', $contents);
            $this->assertStringContainsString('function run(', $contents);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Empty slug must be rejected before touching the filesystem.
     *
     * @return void
     */
    public function testCreateRejectsEmptySlug(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = new Seeder($dir, new InMemorySeederDatabase());

            $this->expectException(\InvalidArgumentException::class);
            $seeder->create('   ');
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Status must list created seeds as pending.
     *
     * @return void
     */
    public function testStatusListsPending(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = new Seeder($dir, new InMemorySeederDatabase());
            $first = $seeder->create('add_users');
            $second = $seeder->create('add_orders');

            $status = $seeder->status();

            $this->assertSame([], $status['applied']);
            $this->assertContains($first, $status['pending']);
            $this->assertContains($second, $status['pending']);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Run must apply pending seeds once inside transactions.
     *
     * @return void
     */
    public function testRunPendingTracksRows(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $seeder = new Seeder($dir, $db);
            $first = $seeder->create('add_users');
            $second = $seeder->create('add_orders');

            $ran = $seeder->run();

            $expected = [$first, $second];
            sort($expected);
            $this->assertSame($expected, $ran);
            $this->assertGreaterThan(0, $db->transactions);

            $status = $seeder->status();

            $this->assertContains($first, $status['applied']);
            $this->assertContains($second, $status['applied']);
            $this->assertSame([], $status['pending']);
            $this->assertSame([], $seeder->run());
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Single-run must apply only the named seed.
     *
     * @return void
     */
    public function testSingleRun(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $seeder = new Seeder($dir, $db);
            $first = $seeder->create('add_users');
            $second = $seeder->create('add_orders');

            $ran = $seeder->run($first);

            $this->assertSame([$first], $ran);

            $status = $seeder->status();

            $this->assertContains($first, $status['applied']);
            $this->assertContains($second, $status['pending']);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Unknown seed name must throw without side effects.
     *
     * @return void
     */
    public function testUnknownNameThrows(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = new Seeder($dir, new InMemorySeederDatabase());
            $seeder->create('add_users');

            $this->expectException(\InvalidArgumentException::class);
            $seeder->run('missing_seed_xyz');
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Traversal names must be rejected before filesystem access.
     *
     * @return void
     */
    public function testTraversalGuardThrows(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = new Seeder($dir, new InMemorySeederDatabase());

            $this->expectException(\InvalidArgumentException::class);
            $seeder->run('../evil');
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Failing seeder must not record a tracking row.
     *
     * @return void
     */
    public function testFailingSeederDoesNotTrack(): void
    {
        $dir = $this->makeTempDir();

        try {
            $code = '<?php' . PHP_EOL . PHP_EOL
                . 'use App\Database\SeederInterface;' . PHP_EOL
                . 'use Roolith\Store\Interfaces\DatabaseInterface;' . PHP_EOL . PHP_EOL
                . 'class SeederFailGuard implements SeederInterface' . PHP_EOL
                . '{' . PHP_EOL
                . '    public function run(DatabaseInterface $db): void' . PHP_EOL
                . '    {' . PHP_EOL
                . '        throw new RuntimeException(\'seed boom\');' . PHP_EOL
                . '    }' . PHP_EOL
                . '}' . PHP_EOL;
            file_put_contents($dir . '/seeder_fail_guard.php', $code);

            $db = new InMemorySeederDatabase();
            $seeder = new Seeder($dir, $db);

            try {
                $seeder->run('seeder_fail_guard');
                $this->fail('Expected seeder failure was not thrown.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('seed boom', $e->getMessage());
            }

            $this->assertSame([], $seeder->applied());
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Applied read failure must fail closed, never silent empty.
     *
     * @return void
     */
    public function testAppliedFailClosed(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $db->failSelect = true;
            $seeder = new Seeder($dir, $db);

            $this->expectException(\RuntimeException::class);
            $seeder->applied();
        } finally {
            $this->removeTempDir($dir);
        }
    }
}
