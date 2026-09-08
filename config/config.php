<?php
use App\Core\Env;

// Minimal runtime config: Env only. See config/config.md for advanced keys.

/**
 * Minimal app config with Env-only values.
 *
 * Version is simple: explicit APP_VERSION wins, else time() in development
 * for no-cache dev, else static 1.0.0 in prod until the user sets a fixed
 * version.
 *
 * @return array{baseUrl: string, viteDevServer: string, database: array{host: string, name: string, user: string, pass: string}|null, forceNonWww: bool, version: string}
 */
return [
    "baseUrl" => Env::get('APP_URL', 'http://localhost:8080/'),
    "viteDevServer" => Env::get('VITE_DEV_SERVER', ''),
    "database" => Env::get('DB_HOST') !== null && Env::get('DB_NAME') !== null ? [
        "host" => (string) Env::get('DB_HOST'),
        "name" => (string) Env::get('DB_NAME'),
        "user" => Env::get('DB_USER', ''),
        "pass" => Env::get('DB_PASS', ''),
    ] : null,
    "forceNonWww" => filter_var(Env::get('FORCE_NON_WWW', '1'), FILTER_VALIDATE_BOOLEAN),
    "version" => Env::get('APP_VERSION', Env::isDevelopment() ? (string) time() : '1.0.0'),
];
