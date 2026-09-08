<?php
namespace App\Console;

/**
 * CLI entry for framework maintenance commands.
 *
 * Handles `php roolith route:list` (lint plus display),
 * `php roolith migrate` commands (create, run, status, rollback), and
 * `php roolith seed` commands plus `seeder:` aliases (create, run, status).
 * Help output covers `help`, `--help`, and `-h`. Unknown commands
 * return 1 without side effects; generator delegation lives in the
 * `roolith` entrypoint, not here. All methods never echo secrets and
 * return integer exit codes.
 */
final class Cli
{
    /**
     * Run the CLI with raw argv including the script name.
     *
     * Dispatches route:list, migrate commands, and seed plus seeder
     * commands locally plus help output; unknown commands return 1. Generator fallback is
     * handled by the `roolith` entrypoint which routes non-framework
     * commands to GeneratorFactory, so this class never shells out.
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

        if ($command === 'migrate' || $command === 'migrate:status' || $command === 'migrate:create' || $command === 'migrate:rollback') {
            return self::migrate($args);
        }

        if ($command === 'seed' || $command === 'seed:status' || $command === 'seed:create' || $command === 'seed:run' || $command === 'seeder:create' || $command === 'seeder:run') {
            return self::seed($args);
        }

        if ($command === '' || $command === 'help' || $command === '--help' || $command === '-h') {
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
        echo '  php roolith migrate:status' . PHP_EOL;
        echo '  php roolith migrate:create <Name>' . PHP_EOL;
        echo '  php roolith migrate:rollback [Name]' . PHP_EOL;
        echo '  php roolith seed' . PHP_EOL;
        echo '  php roolith seed:status' . PHP_EOL;
        echo '  php roolith seed:create <Name>' . PHP_EOL;
        echo '  php roolith seed:run [Name]' . PHP_EOL;
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
     * Run migration subcommands.
     *
     * Supports migrate (pending only), migrate:status, migrate:create,
     * and migrate:rollback. Boots Env plus Config so database config
     * resolves, connects via DatabaseFactory, then delegates to
     * App\Database\Migrator. Never prints credentials.
     *
     * @param array<int, string> $args CLI arguments without script name.
     * @return int Exit code (0 success, 1 failure).
     */
    public static function migrate(array $args): int
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

        try {
            \App\Core\Env::load((string) APP_ROOT);
            require_once (string) APP_ROOT . '/constant.php';
            require_once (string) APP_ROOT . '/app/Utils/functions.php';
            $config = require (string) APP_ROOT . '/config/config.php';

            if (!is_array($config)) {
                echo 'Invalid config/config.php.' . PHP_EOL;

                return 1;
            }

            $database = isset($config['database']) && is_array($config['database']) ? $config['database'] : null;

            if ($database !== null) {
                $connected = \App\Core\DatabaseFactory::getInstance()->connect($database);

                if (!$connected) {
                    echo 'Database connection failed.' . PHP_EOL;

                    return 1;
                }
            }

            $migrator = new \App\Database\Migrator();
            $sub = isset($args[0]) ? (string) $args[0] : 'migrate';

            if ($sub === 'migrate:status') {
                $status = $migrator->status();
                echo 'Applied: ' . count($status['applied']) . PHP_EOL;

                foreach ($status['applied'] as $name) {
                    echo '  [applied] ' . $name . PHP_EOL;
                }

                echo 'Pending: ' . count($status['pending']) . PHP_EOL;

                foreach ($status['pending'] as $name) {
                    echo '  [pending] ' . $name . PHP_EOL;
                }

                return 0;
            }

            if ($sub === 'migrate:create') {
                $name = isset($args[1]) ? trim((string) $args[1]) : '';

                if ($name === '') {
                    echo 'Usage: php roolith migrate:create <Name>' . PHP_EOL;

                    return 1;
                }

                $created = $migrator->create($name);
                echo 'Created ' . $created . PHP_EOL;

                return 0;
            }

            if ($sub === 'migrate:rollback') {
                $name = isset($args[1]) && trim((string) $args[1]) !== '' ? trim((string) $args[1]) : null;
                $done = $migrator->rollback($name);

                if ($done === []) {
                    echo 'Nothing to roll back.' . PHP_EOL;
                } else {
                    foreach ($done as $rolledBack) {
                        echo 'Rolled back ' . $rolledBack . PHP_EOL;
                    }
                }

                return 0;
            }

            $ran = $migrator->run();

            if ($ran === []) {
                echo 'Nothing to migrate.' . PHP_EOL;
            } else {
                foreach ($ran as $migrated) {
                    echo 'Migrated ' . $migrated . PHP_EOL;
                }
            }

            return 0;
        } catch (\Throwable $e) {
            echo 'Migration failed: ' . substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500) . PHP_EOL;

            return 1;
        }
    }

    /**
     * Run seeder subcommands.
     *
     * Supports seed (pending only), seed:status, seed:create,
     * seed:run with an optional name, plus seeder:create and
     * seeder:run upstream aliases. Boots Env plus Config so database
     * config resolves, connects via DatabaseFactory, then delegates
     * to App\Database\Seeder. Never prints credentials.
     *
     * @param array<int, string> $args CLI arguments without script name.
     * @return int Exit code (0 success, 1 failure).
     */
    public static function seed(array $args): int
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

        try {
            \App\Core\Env::load((string) APP_ROOT);
            require_once (string) APP_ROOT . '/constant.php';
            require_once (string) APP_ROOT . '/app/Utils/functions.php';
            $config = require (string) APP_ROOT . '/config/config.php';

            if (!is_array($config)) {
                echo 'Invalid config/config.php.' . PHP_EOL;

                return 1;
            }

            $database = isset($config['database']) && is_array($config['database']) ? $config['database'] : null;

            if ($database !== null) {
                $connected = \App\Core\DatabaseFactory::getInstance()->connect($database);

                if (!$connected) {
                    echo 'Database connection failed.' . PHP_EOL;

                    return 1;
                }
            }

            $seeder = new \App\Database\Seeder();
            $sub = isset($args[0]) ? (string) $args[0] : 'seed';

            if ($sub === 'seed:status') {
                $status = $seeder->status();
                echo 'Applied: ' . count($status['applied']) . PHP_EOL;

                foreach ($status['applied'] as $name) {
                    echo '  [applied] ' . $name . PHP_EOL;
                }

                echo 'Pending: ' . count($status['pending']) . PHP_EOL;

                foreach ($status['pending'] as $name) {
                    echo '  [pending] ' . $name . PHP_EOL;
                }

                return 0;
            }

            if ($sub === 'seed:create' || $sub === 'seeder:create') {
                $name = isset($args[1]) ? trim((string) $args[1]) : '';

                if ($name === '') {
                    echo 'Usage: php roolith seed:create <Name>' . PHP_EOL;

                    return 1;
                }

                $created = $seeder->create($name);
                echo 'Created ' . $created . PHP_EOL;

                return 0;
            }

            if ($sub === 'seed:run' || $sub === 'seeder:run') {
                $name = isset($args[1]) && trim((string) $args[1]) !== '' ? trim((string) $args[1]) : null;
                $done = $seeder->run($name);

                if ($done === []) {
                    echo 'Nothing to seed.' . PHP_EOL;
                } else {
                    foreach ($done as $seeded) {
                        echo 'Seeded ' . $seeded . PHP_EOL;
                    }
                }

                return 0;
            }

            $ran = $seeder->run();

            if ($ran === []) {
                echo 'Nothing to seed.' . PHP_EOL;
            } else {
                foreach ($ran as $seeded) {
                    echo 'Seeded ' . $seeded . PHP_EOL;
                }
            }

            return 0;
        } catch (\Throwable $e) {
            echo 'Seeder failed: ' . substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500) . PHP_EOL;

            return 1;
        }
    }
}
