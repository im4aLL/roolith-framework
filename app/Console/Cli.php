<?php
namespace App\Console;

/**
 * CLI entry for framework maintenance commands.
 *
 * Handles `php roolith route:list` (lint plus display),
 * migration commands (`migrate` family as BC aliases of
 * `migration:*` from `roolith/migration`), and seeder commands
 * (`seed` family plus `seeder:*`). Help output covers `help`,
 * `--help`, and `-h`. Unknown commands return 1 without side
 * effects; generator delegation lives in the `roolith` entrypoint,
 * not here. All methods never echo secrets and return integer exit codes.
 */
final class Cli
{
    /**
     * Migrate family (BC aliases) to native migration:* commands.
     *
     * Single source of truth for every migrate/migration command the
     * CLI handles; Cli::run(), isFrameworkCommand(), and migrate()
     * all read this map.
     *
     * @var array<string, string>
     */
    private const MIGRATE_MAP = [
        'migrate' => 'migration:run',
        'migrate:run' => 'migration:run',
        'migration:run' => 'migration:run',
        'migrate:status' => 'migration:status',
        'migration:status' => 'migration:status',
        'migrate:create' => 'migration:create',
        'migration:create' => 'migration:create',
        'migrate:rollback' => 'migration:rollback',
        'migration:rollback' => 'migration:rollback',
    ];

    /**
     * Seed family (plus seeder:* natives) to native seeder:* commands.
     *
     * Single source of truth for every seed/seeder command the CLI
     * handles; Cli::run(), isFrameworkCommand(), and seed() all read
     * this map.
     *
     * @var array<string, string>
     */
    private const SEED_MAP = [
        'seed' => 'seeder:run',
        'seed:run' => 'seeder:run',
        'seeder:run' => 'seeder:run',
        'seed:status' => 'seeder:status',
        'seeder:status' => 'seeder:status',
        'seed:create' => 'seeder:create',
        'seeder:create' => 'seeder:create',
    ];

    /**
     * Help aliases handled locally.
     *
     * @var array<int, string>
     */
    private const HELP_COMMANDS = ['help', '--help', '-h'];

    /**
     * Check whether a command is handled by the framework CLI.
     *
     * Used by the `roolith` entrypoint so the framework command list
     * lives in exactly one place; generator fallback handles the rest.
     *
     * @param string $command CLI command without script name.
     * @return bool True for route:list, help, and every migrate/seed key.
     */
    public static function isFrameworkCommand(string $command): bool
    {
        return $command === 'route:list'
            || isset(self::MIGRATE_MAP[$command])
            || isset(self::SEED_MAP[$command])
            || in_array($command, self::HELP_COMMANDS, true);
    }

    /**
     * Run the CLI with raw argv including the script name.
     *
     * Dispatches route:list, migration commands (`migrate` family plus
     * native `migration:*`), and seeder commands (`seed` family plus
     * native `seeder:*`) locally plus help output; unknown commands
     * return 1. Generator fallback is handled by the `roolith`
     * entrypoint which routes non-framework commands to
     * GeneratorFactory, so this class never shells out.
     * Returns 0 on success, 1 on failure.
     *
     * @param array<int, string> $argv Raw CLI arguments including script name.
     * @return int Exit code.
     */
    public static function run(array $argv): int
    {
        $args = $argv;
        array_shift($args);
        $command = isset($args[0]) ? (string) $args[0] : '';

        if ($command === 'route:list') {
            return self::routeList();
        }

        if (isset(self::MIGRATE_MAP[$command])) {
            return self::migrate($args);
        }

        if (isset(self::SEED_MAP[$command])) {
            return self::seed($args);
        }

        if ($command === '' || in_array($command, self::HELP_COMMANDS, true)) {
            self::printHelp();

            return 0;
        }

        return 1;
    }

    /**
     * Print CLI help text.
     *
     * @return void
     */
    public static function printHelp(): void
    {
        echo 'Roolith CLI' . PHP_EOL;
        echo '  php roolith generate <type> <Name>' . PHP_EOL;
        echo '  php roolith route:list' . PHP_EOL;
        echo '  php roolith migrate' . PHP_EOL;
        echo '  php roolith migrate:status [Name]' . PHP_EOL;
        echo '  php roolith migrate:create <Name>' . PHP_EOL;
        echo '  php roolith migrate:rollback <Name>' . PHP_EOL;
        echo '  php roolith seed' . PHP_EOL;
        echo '  php roolith seed:status [Name]' . PHP_EOL;
        echo '  php roolith seed:create <Name>' . PHP_EOL;
        echo '  php roolith seed:run [Name]' . PHP_EOL;
        echo '  (migration:* and seeder:* natives accepted)' . PHP_EOL;
    }

    /**
     * List routes and lint string handlers.
     *
     * Boots the router via System::processRequest() in a dry-run that
     * never emits, prints the vendor ASCII table via
     * formattedRouteList(), then validates Controller@method strings
     * with class_exists plus method_exists.
     * Exits 1 when any handler is invalid so CI can gate on it.
     *
     * @return int Exit code (0 valid, 1 invalid).
     */
    public static function routeList(): int
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__, 2));
        }

        require_once (string) APP_ROOT . '/vendor/autoload.php';

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', (string) APP_ROOT . '/config');
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', (string) APP_ROOT . '/views');
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        \App\Core\RouterFactory::reset();
        $router = require (string) APP_ROOT . '/app/Http/routes.php';

        if (!is_object($router) || !method_exists($router, 'getRouteList')) {
            echo 'Router bootstrap failed: routes.php must return a router.' . PHP_EOL;

            return 1;
        }

        /** @var array<int, array<string, mixed>> $routes */
        $routes = $router->getRouteList();

        echo $router->formattedRouteList();

        $errors = \App\Core\RouteValidator::validateRouteList($routes);

        if ($errors === []) {
            echo 'All route handlers valid (' . count($routes) . ' routes). Prefer [Class::class, \'method\'] callable syntax.' . PHP_EOL;

            return 0;
        }

        foreach ($errors as $error) {
            echo 'INVALID: ' . $error . PHP_EOL;
        }

        return 1;
    }

    /**
     * Boot framework paths plus config and return the database section.
     *
     * Shared by migrate() and seed() so Env loading, path defines, and
     * the database-null check live in one place. Echoes the reason and
     * returns null when config.php is invalid or has no database
     * section; throws when Env or bootstrap itself fails so callers
     * keep their own failure prefix. Never prints credentials.
     *
     * @return array<string, mixed>|null Database config, or null when invalid.
     */
    private static function bootDatabaseConfig(): array|null
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__, 2));
        }

        require_once (string) APP_ROOT . '/vendor/autoload.php';

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', (string) APP_ROOT . '/config');
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', (string) APP_ROOT . '/views');
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        \App\Core\Env::load((string) APP_ROOT);
        require_once (string) APP_ROOT . '/constant.php';
        require_once (string) APP_ROOT . '/app/Utils/functions.php';
        $config = require (string) APP_ROOT . '/config/config.php';

        if (!is_array($config)) {
            echo 'Invalid config/config.php.' . PHP_EOL;

            return null;
        }

        $database = isset($config['database']) && is_array($config['database']) ? $config['database'] : null;

        if ($database === null) {
            echo 'Database connection failed.' . PHP_EOL;

            return null;
        }

        return $database;
    }

    /**
     * Run migration subcommands via roolith/migration.
     *
     * Accepts the `migrate` family (`migrate`, `migrate:run`,
     * `migrate:status`, `migrate:create`, `migrate:rollback`) as BC
     * aliases of the native `migration:*` commands. Boots Env plus
     * Config so database config resolves, then delegates to
     * `App\Database\MigrationFactory` plus the package `run()` (which
     * echoes progress and returns the exit code). Bare `migrate`
     * runs all pending; `migrate [Name]` runs one named migration.
     * Rollback requires a name; batch rollback from the old internal
     * runner no longer exists. Never prints credentials.
     *
     * @param array<int, string> $args CLI arguments without script name.
     * @return int Exit code (0 success, 1 failure).
     */
    public static function migrate(array $args): int
    {
        try {
            if (count($args) > 2) {
                echo 'Usage: php roolith migrate[:status|:create|:run] [Name], php roolith migrate:rollback <Name>' . PHP_EOL;

                return 1;
            }

            $database = self::bootDatabaseConfig();

            if ($database === null) {
                return 1;
            }

            $sub = isset($args[0]) ? (string) $args[0] : 'migrate';
            $name = isset($args[1]) ? trim((string) $args[1]) : '';
            $map = self::MIGRATE_MAP;

            if (!isset($map[$sub])) {
                echo 'Usage: php roolith migrate[:status|:create|:run] [Name], php roolith migrate:rollback <Name>' . PHP_EOL;

                return 1;
            }

            $migration = \App\Database\MigrationFactory::forMigrations(null, null, $database);

            return $migration->run(['roolith', $map[$sub], $name]);
        } catch (\Throwable $e) {
            echo 'Migration failed: ' . substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500) . PHP_EOL;

            return 1;
        }
    }

    /**
     * Run seeder subcommands via roolith/migration.
     *
     * Accepts `seed` (run pending), `seed:status` / `seeder:status`,
     * `seed:create` / `seeder:create`, and `seed:run` / `seeder:run`
     * with an optional name. Boots Env plus Config so database config
     * resolves, then delegates to `App\Database\MigrationFactory`
     * (seeders folder, shared status table) plus the package `run()`.
     * Bare `seed` runs all pending; `seed [Name]` runs one named
     * seeder. Never prints credentials.
     *
     * @param array<int, string> $args CLI arguments without script name.
     * @return int Exit code (0 success, 1 failure).
     */
    public static function seed(array $args): int
    {
        try {
            if (count($args) > 2) {
                echo 'Usage: php roolith seed[:status|:create|:run] [Name]' . PHP_EOL;

                return 1;
            }

            $database = self::bootDatabaseConfig();

            if ($database === null) {
                return 1;
            }

            $sub = isset($args[0]) ? (string) $args[0] : 'seed';
            $name = isset($args[1]) ? trim((string) $args[1]) : '';
            $map = self::SEED_MAP;

            if (!isset($map[$sub])) {
                echo 'Usage: php roolith seed[:status|:create|:run] [Name]' . PHP_EOL;

                return 1;
            }

            $seeder = \App\Database\MigrationFactory::forSeeders(null, null, $database);

            return $seeder->run(['roolith', $map[$sub], $name]);
        } catch (\Throwable $e) {
            echo 'Seeder failed: ' . substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500) . PHP_EOL;

            return 1;
        }
    }
}
