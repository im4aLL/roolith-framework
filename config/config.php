<?php
use App\Core\Env;

// Runtime config maps environment with safe defaults. Copy .env.example to
// .env for local overrides. APP_ENV itself defaults to production (see
// App\Core\Env) so an unset env boots fail-closed.
// Note: Env::load runs in System::__construct before Config first reads this
// file, and the composer autoloader is already registered, so Env is
// available here. Env::get treats empty string as unset but keeps "0".

$dbHost = Env::get('DB_HOST');
$dbName = Env::get('DB_NAME');

return [
    /**
     * Base URL for app
     */
    "baseUrl" => Env::get('APP_URL', 'http://localhost:8080/'),

    /**
     * Vite dev server url used in development to serve assets with HMR
     *
     * Set an empty string to always use built assets from the assets folder
     */
    "viteDevServer" => Env::get('VITE_DEV_SERVER', ''),

    /**
     * Database configuration (null runs without a database)
     */
    "database" => ($dbHost !== null && $dbName !== null) ? [
        "host" => $dbHost,
        "name" => $dbName,
        "user" => Env::get('DB_USER', ''),
        "pass" => Env::get('DB_PASS', ''),
    ] : null,

    /**
     * For domain to have www or not www in domain
     */
    "forceNonWww" => filter_var(Env::get('FORCE_NON_WWW', '1'), FILTER_VALIDATE_BOOLEAN),

    /**
     * Current app version
     */
    "version" => Env::get('APP_VERSION', (string) time()),

    /**
     * Log file path (LOG_PATH override, defaults to storage/logs/app.log)
     */
    "logPath" => Env::get('LOG_PATH', APP_ROOT . '/storage/logs/app.log'),

    /**
     * Routine log output (LOG_ENABLED override, defaults to off).
     *
     * False drops debug/info/notice lines; warning and above are always written.
     */
    "logEnabled" => filter_var(Env::get('LOG_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN),
];
