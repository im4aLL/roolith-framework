<?php
namespace Tests;

use App\Console\Cli;
use App\Database\MigrationFactory;
use PHPUnit\Framework\TestCase;
use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Paginate;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;
use Tests\Support\InMemorySeederDatabase;

/**
 * Covers the package-backed seeder and migration runners.
 *
 * The framework no longer ships its own runner: creation, run, and
 * status delegate to `roolith/migration` through
 * `App\Database\MigrationFactory`. These tests pin the integration
 * (shared status table, separate folders, seeder file suffix, BC
 * interfaces) with an in-memory double plus temp dirs.
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
     * Factory must share one status table with separate folders.
     *
     * @return void
     */
    public function testFactoryDefaults(): void
    {
        $this->assertSame('migrations', MigrationFactory::TABLE);
        $this->assertSame('database/migrations', MigrationFactory::MIGRATIONS_DIR);
        $this->assertSame('database/seeders', MigrationFactory::SEEDERS_DIR);
    }

    /**
     * BC interfaces must extend the vendor contracts.
     *
     * @return void
     */
    public function testBcInterfacesExtendVendor(): void
    {
        $this->assertTrue(is_subclass_of(\App\Database\MigrationInterface::class, \Roolith\Migration\Interfaces\MigrationInterface::class));
        $this->assertTrue(is_subclass_of(\App\Database\SeederInterface::class, \Roolith\Migration\Interfaces\SeederInterface::class));
    }

    /**
     * Seeder create must write a .seeder.php file plus a DB row.
     *
     * @return void
     */
    public function testCreateWritesSeederFile(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $seeder = MigrationFactory::create($dir, $db);

            $exit = $seeder->run(['roolith', 'seeder:create', 'add_users']);

            $this->assertSame(0, $exit);

            $files = glob($dir . '/*.seeder.php') ?: [];
            $this->assertCount(1, $files);
            $this->assertStringContainsString('add_users', basename($files[0]));

            $contents = (string) file_get_contents($files[0]);
            $this->assertStringContainsString('implements SeederInterface', $contents);
            $this->assertStringContainsString('function run(', $contents);

            $rows = $db->table(MigrationFactory::TABLE)->where('file_type', 'seeder')->get();
            $this->assertCount(1, $rows);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Empty seeder names must fail with a non-zero exit, not a crash.
     *
     * @return void
     */
    public function testCreateRejectsEmptySlug(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = MigrationFactory::create($dir, new InMemorySeederDatabase());

            $this->assertSame(1, $seeder->run(['roolith', 'seeder:create', '   ']));
            $this->assertSame([], glob($dir . '/*.seeder.php') ?: []);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Run must execute pending seeders and mark them completed.
     *
     * @return void
     */
    public function testRunPendingMarksCompleted(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $seeder = MigrationFactory::create($dir, $db);
            $seeder->run(['roolith', 'seeder:create', 'add_users']);
            $seeder->run(['roolith', 'seeder:create', 'add_orders']);

            $exit = $seeder->run(['roolith', 'seeder:run']);

            $this->assertSame(0, $exit);

            $rows = $db->table(MigrationFactory::TABLE)->where('file_type', 'seeder')->where('status', 'completed')->get();
            $this->assertCount(2, $rows);

            $pending = $db->table(MigrationFactory::TABLE)->where('file_type', 'seeder')->where('status', 'pending')->get();
            $this->assertSame([], $pending);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Unknown seeder names must fail closed with exit 1.
     *
     * @return void
     */
    public function testUnknownNameFails(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = MigrationFactory::create($dir, new InMemorySeederDatabase());
            $seeder->run(['roolith', 'seeder:create', 'add_users']);

            $this->assertSame(1, $seeder->run(['roolith', 'seeder:run', 'missing_seed_xyz']));
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Traversal names must be rejected before filesystem access.
     *
     * @return void
     */
    public function testTraversalGuardFails(): void
    {
        $dir = $this->makeTempDir();

        try {
            $seeder = MigrationFactory::create($dir, new InMemorySeederDatabase());

            $this->assertSame(1, $seeder->run(['roolith', 'seeder:run', '../evil']));
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Migration create plus run must work through the same factory.
     *
     * @return void
     */
    public function testMigrationCreateAndRun(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $migration = MigrationFactory::create($dir, $db);

            $this->assertSame(0, $migration->run(['roolith', 'migration:create', 'create_users_table']));

            $files = glob($dir . '/*.migration.php') ?: [];
            $this->assertCount(1, $files);

            $this->assertSame(0, $migration->run(['roolith', 'migration:run']));

            $rows = $db->table(MigrationFactory::TABLE)->where('file_type', 'migration')->where('status', 'completed')->get();
            $this->assertCount(1, $rows);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Failing seeders must be marked failed, never completed.
     *
     * @return void
     */
    public function testFailingSeederMarkedFailed(): void
    {
        $dir = $this->makeTempDir();

        try {
            $code = '<?php' . PHP_EOL . PHP_EOL
                . 'use Roolith\Migration\Interfaces\SeederInterface;' . PHP_EOL
                . 'use Roolith\Store\Interfaces\DatabaseInterface;' . PHP_EOL . PHP_EOL
                . 'class SeederFailGuard implements SeederInterface' . PHP_EOL
                . '{' . PHP_EOL
                . '    public function run(DatabaseInterface $db): void' . PHP_EOL
                . '    {' . PHP_EOL
                . '        throw new RuntimeException(\'seed boom\');' . PHP_EOL
                . '    }' . PHP_EOL
                . '}' . PHP_EOL;
            file_put_contents($dir . '/seeder_fail_guard.seeder.php', $code);

            $db = new InMemorySeederDatabase();
            $db->table(MigrationFactory::TABLE)->insert([
                'name' => 'seeder_fail_guard',
                'file_type' => 'seeder',
                'created_at' => '2025-01-01 00:00:00',
            ]);

            $seeder = MigrationFactory::create($dir, $db);

            $this->assertSame(1, $seeder->run(['roolith', 'seeder:run', 'seeder_fail_guard']));

            $rows = $db->table(MigrationFactory::TABLE)->where('name', 'seeder_fail_guard')->get();
            $this->assertCount(1, $rows);
            $this->assertSame('failed', $rows[0]->status);
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Rollback must flip one completed migration back to pending.
     *
     * Rollback without a name must fail closed with exit 1.
     *
     * @return void
     */
    public function testRollbackFlipsCompletedToPending(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $migration = MigrationFactory::create($dir, $db);

            $this->assertSame(0, $migration->run(['roolith', 'migration:create', 'create_users_table']));
            $this->assertSame(0, $migration->run(['roolith', 'migration:run']));

            $completed = $db->table(MigrationFactory::TABLE)->where('file_type', 'migration')->where('status', 'completed')->get();
            $this->assertCount(1, $completed);

            $this->assertSame(0, $migration->run(['roolith', 'migration:rollback', 'create_users_table']));

            $pending = $db->table(MigrationFactory::TABLE)->where('file_type', 'migration')->where('status', 'pending')->get();
            $this->assertCount(1, $pending);

            $this->assertSame(1, $migration->run(['roolith', 'migration:rollback']));
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * Status commands must exit 0 for known rows, 1 for unknown names.
     *
     * @return void
     */
    public function testStatusCommands(): void
    {
        $dir = $this->makeTempDir();

        try {
            $db = new InMemorySeederDatabase();
            $runner = MigrationFactory::create($dir, $db);

            $this->assertSame(0, $runner->run(['roolith', 'migration:create', 'create_users_table']));
            $this->assertSame(0, $runner->run(['roolith', 'seeder:create', 'add_users']));

            $this->assertSame(0, $runner->run(['roolith', 'migration:status']));
            $this->assertSame(0, $runner->run(['roolith', 'seeder:status']));

            $this->assertSame(1, $runner->run(['roolith', 'migration:status', 'missing_migration_xyz']));
            $this->assertSame(1, $runner->run(['roolith', 'seeder:status', 'missing_seeder_xyz']));
        } finally {
            $this->removeTempDir($dir);
        }
    }

    /**
     * migrate:run must route to the migrate path, not unknown-command.
     *
     * With no database configured the migrate path echoes its boot
     * failure; the unknown path stays silent, so output pins routing.
     *
     * @return void
     */
    public function testMigrateRunAliasRoutesToMigrate(): void
    {
        $this->assertTrue(Cli::isFrameworkCommand('migrate:run'));
        $this->assertFalse(Cli::isFrameworkCommand('bogus:command'));

        $keys = ['DB_HOST', 'DB_NAME'];
        $saved = [];

        foreach ($keys as $key) {
            $saved[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        try {
            ob_start();
            $exit = Cli::run(['roolith', 'migrate:run']);
            $output = (string) ob_get_clean();
        } finally {
            foreach ($keys as $key) {
                [$env, $envVar, $serverVar] = $saved[$key];

                if ($env === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $env);
                }

                if ($envVar === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $envVar;
                }

                if ($serverVar === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $serverVar;
                }
            }
        }

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Database connection failed.', $output);
    }
}
