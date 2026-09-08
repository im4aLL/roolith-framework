<?php
namespace App\Database;

use Roolith\Migration\Migration;
use Roolith\Store\Database;
use Roolith\Store\Interfaces\DatabaseInterface;

/**
 * Factory for package-backed migration and seeder runners.
 *
 * Both runners share the single `migrations` status table (with a
 * `file_type` column) from `roolith/migration`, but scan separate
 * folders so the framework keeps `database/migrations` for schema
 * (`*.migration.php`) and `database/seeders` for data
 * (`*.seeder.php`). Each runner gets a fresh `Database` handle unless
 * a test double is injected, because the package `run()` disconnects
 * its handle when done and must never tear down the process-wide
 * `DatabaseFactory` singleton.
 */
final class MigrationFactory
{
    /**
     * Default migrations directory relative to APP_ROOT.
     *
     * @var string
     */
    public const MIGRATIONS_DIR = 'database/migrations';

    /**
     * Default seeders directory relative to APP_ROOT.
     *
     * @var string
     */
    public const SEEDERS_DIR = 'database/seeders';

    /**
     * Shared status table holding both migration and seeder rows.
     *
     * @var string
     */
    public const TABLE = 'migrations';

    /**
     * Private constructor to enforce factory use.
     */
    private function __construct() {}

    /**
     * Build a runner for migration commands.
     *
     * @param string|null $dir Absolute migrations dir (defaults to APP_ROOT/database/migrations).
     * @param DatabaseInterface|null $db Database connection (defaults to a fresh handle).
     * @param array<string, mixed>|null $dbConfig Connection config passed to settings() so the package connects the handle.
     * @return Migration Configured package runner.
     */
    public static function forMigrations(?string $dir = null, ?DatabaseInterface $db = null, ?array $dbConfig = null): Migration
    {
        return self::create($dir ?? self::defaultDir(self::MIGRATIONS_DIR), $db, $dbConfig);
    }

    /**
     * Build a runner for seeder commands.
     *
     * Uses the same status table as migrations; rows are separated by
     * the package `file_type` column.
     *
     * @param string|null $dir Absolute seeders dir (defaults to APP_ROOT/database/seeders).
     * @param DatabaseInterface|null $db Database connection (defaults to a fresh handle).
     * @param array<string, mixed>|null $dbConfig Connection config passed to settings() so the package connects the handle.
     * @return Migration Configured package runner.
     */
    public static function forSeeders(?string $dir = null, ?DatabaseInterface $db = null, ?array $dbConfig = null): Migration
    {
        return self::create($dir ?? self::defaultDir(self::SEEDERS_DIR), $db, $dbConfig);
    }

    /**
     * Build a package runner for an explicit folder.
     *
     * @param string $folder Absolute folder to scan for *.migration.php and *.seeder.php files.
     * @param DatabaseInterface|null $db Database connection (defaults to a fresh handle).
     * @param array<string, mixed>|null $dbConfig Connection config (omitted for injected test doubles).
     * @return Migration Configured package runner.
     */
    public static function create(string $folder, ?DatabaseInterface $db = null, ?array $dbConfig = null): Migration
    {
        $db = $db ?? new Database();
        $settings = ['folder' => $folder, 'table' => self::TABLE];

        if ($dbConfig !== null) {
            $settings['database'] = $dbConfig;
        }

        return (new Migration($db))->settings($settings);
    }

    /**
     * Resolve a default directory under APP_ROOT.
     *
     * @param string $relative Relative dir like database/migrations.
     * @return string Absolute directory path.
     */
    private static function defaultDir(string $relative): string
    {
        $base = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);

        return rtrim($base, "/\\") . '/' . $relative;
    }
}
