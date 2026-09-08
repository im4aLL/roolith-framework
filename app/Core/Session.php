<?php
namespace App\Core;

use Roolith\Configuration\Config;
use Throwable;

/**
 * Single helper owning session startup and cookie params.
 *
 * All session users (front controller, Storage, rate limiter) go through
 * Session::start() so cookie flags stay consistent and session_start()
 * is never called twice. Params come from config with fail-closed
 * defaults: httponly true, SameSite Lax, path /, Secure on https.
 */
final class Session
{
    /**
     * Start the session once with hardened cookie params.
     *
     * Idempotent: returns true immediately when a session is already
     * active. Never throws; returns false when startup fails.
     *
     * @param array<string, mixed> $overrides Per-call param overrides (tests, edge cases).
     * @return bool True when a session is active after the call.
     */
    public static function start(array $overrides = []): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        $params = self::cookieParams($overrides);

        try {
            session_set_cookie_params($params);
        } catch (Throwable) {
            return false;
        }

        try {
            return session_start();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Regenerate the session ID, for use on privilege change (login).
     *
     * Wraps session_regenerate_id(true) and never throws.
     *
     * @param bool $deleteOldSession True to delete the old session data.
     * @return bool True on success, false when no active session or on failure.
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        try {
            return session_regenerate_id($deleteOldSession);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Build session cookie params from config with safe defaults.
     *
     * Pure and testable: no session state is touched. Each key reads
     * Config first, then Env (COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE,
     * COOKIE_SAMESITE, SESSION_LIFETIME), then a fail-closed default,
     * so `.env` alone takes effect without a config key. Overrides are
     * validated (lifetime int >= 0, non-empty path, string domain, bool
     * secure, allowlisted samesite) and httponly is always forced to true
     * so callers can never weaken it.
     *
     * @param array<string, mixed> $overrides Values winning over config.
     * @return array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string} Cookie params for session_set_cookie_params().
     */
    public static function cookieParams(array $overrides = []): array
    {
        $params = [
            'lifetime' => self::configInt('sessionLifetime', 0),
            'path' => self::configString('cookiePath', '/'),
            'domain' => self::configString('cookieDomain', ''),
            'secure' => self::configBool('cookieSecure', self::defaultSecure()),
            'httponly' => true,
            'samesite' => self::configSameSite(),
        ];

        foreach ($overrides as $key => $value) {
            if (!array_key_exists($key, $params)) {
                continue;
            }

            if ($key === 'httponly') {
                continue;
            }

            if ($key === 'lifetime') {
                if (is_int($value) && $value >= 0) {
                    $params[$key] = $value;
                }

                continue;
            }

            if ($key === 'path') {
                if (is_string($value) && trim($value) !== '') {
                    $params[$key] = $value;
                }

                continue;
            }

            if ($key === 'domain') {
                if (is_string($value)) {
                    $params[$key] = $value;
                }

                continue;
            }

            if ($key === 'secure') {
                if (is_bool($value)) {
                    $params[$key] = $value;
                }

                continue;
            }

            if ($key === 'samesite') {
                if (in_array($value, ['Lax', 'Strict', 'None'], true)) {
                    $params[$key] = $value;
                }

                continue;
            }
        }

        $params['httponly'] = true;

        return $params;
    }

    /**
     * Derive the default Secure flag from the base URL scheme.
     *
     * True on https base URLs (fail-closed prod), false on plain http so
     * local dev sessions still work. Unknown base URLs default to true.
     * Reads Config `baseUrl` first, then Env `APP_URL`, so `.env` alone
     * takes effect without re-adding a config key. Never throws.
     *
     * @return bool True when cookies should default to Secure.
     */
    public static function defaultSecure(): bool
    {
        $baseUrl = self::configStringOrNull('baseUrl');

        if ($baseUrl === null) {
            return true;
        }

        return str_starts_with(strtolower(trim($baseUrl)), 'https://');
    }

    /**
     * Read an Env var without ever throwing.
     *
     * Empty or whitespace-only values count as unset via Env::get and
     * return null so hardcoded defaults apply.
     *
     * @param string|null $envKey Env var name or null when no fallback exists.
     * @return string|null Trimmed Env value or null when unset.
     */
    private static function readEnv(?string $envKey): ?string
    {
        if ($envKey === null) {
            return null;
        }

        try {
            return Env::get($envKey);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Map a string config key to its Env var name.
     *
     * @param string $key Config key to map.
     * @return string|null Env var name or null when no Env fallback exists.
     */
    private static function envKeyForString(string $key): ?string
    {
        return match ($key) {
            'cookiePath' => 'COOKIE_PATH',
            'cookieDomain' => 'COOKIE_DOMAIN',
            default => null,
        };
    }

    /**
     * Read a string config key with a default, never throwing.
     *
     * Config wins when present; otherwise falls back to Env
     * (COOKIE_PATH, COOKIE_DOMAIN), then the hardcoded default, so
     * setting `.env` alone takes effect without a config key.
     *
     * @param string $key Config key to read.
     * @param string $default Fallback when missing or not a string.
     * @return string Configured string or the default.
     */
    private static function configString(string $key, string $default): string
    {
        $value = self::readConfig($key);

        if (is_string($value) && ($key !== 'cookiePath' || trim($value) !== '')) {
            return $value;
        }

        $envValue = self::readEnv(self::envKeyForString($key));

        if (is_string($envValue) && ($key !== 'cookiePath' || trim($envValue) !== '')) {
            return $envValue;
        }

        return $default;
    }

    /**
     * Read a string config key or null, never throwing.
     *
     * Config wins when present; `baseUrl` falls back to Env `APP_URL`
     * so defaultSecure() stays Env-aware without a config key.
     *
     * @param string $key Config key to read.
     * @return string|null Configured string or null when missing.
     */
    private static function configStringOrNull(string $key): ?string
    {
        $value = self::readConfig($key);

        if (is_string($value)) {
            return $value;
        }

        if ($key === 'baseUrl') {
            return self::readEnv('APP_URL');
        }

        return null;
    }

    /**
     * Read an int config key with a default, never throwing.
     *
     * Config wins when a valid int >= 0; otherwise falls back to Env
     * `SESSION_LIFETIME` cast with (int), then the hardcoded default.
     *
     * @param string $key Config key to read.
     * @param int $default Fallback when missing or invalid.
     * @return int Configured int or the default.
     */
    private static function configInt(string $key, int $default): int
    {
        $value = self::readConfig($key);

        if (is_int($value) && $value >= 0) {
            return $value;
        }

        if ($key === 'sessionLifetime') {
            $envValue = self::readEnv('SESSION_LIFETIME');

            if ($envValue !== null) {
                $parsed = (int) $envValue;

                if ($parsed >= 0) {
                    return $parsed;
                }
            }
        }

        return $default;
    }

    /**
     * Read a bool config key with a default, never throwing.
     *
     * Config wins when a bool; otherwise falls back to Env
     * `COOKIE_SECURE` parsed with filter_var, then the default.
     *
     * @param string $key Config key to read.
     * @param bool $default Fallback when missing or not a bool.
     * @return bool Configured bool or the default.
     */
    private static function configBool(string $key, bool $default): bool
    {
        $value = self::readConfig($key);

        if (is_bool($value)) {
            return $value;
        }

        if ($key === 'cookieSecure') {
            $envValue = self::readEnv('COOKIE_SECURE');

            if ($envValue !== null) {
                return filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $default;
    }

    /**
     * Read the SameSite config with a Lax default.
     *
     * Config wins when allowlisted; otherwise falls back to Env
     * `COOKIE_SAMESITE` allowlisted, then Lax. Never throws.
     *
     * @return string One of Lax, Strict, None.
     */
    private static function configSameSite(): string
    {
        $value = self::readConfig('cookieSameSite');

        if (in_array($value, ['Lax', 'Strict', 'None'], true)) {
            return $value;
        }

        $envValue = self::readEnv('COOKIE_SAMESITE');

        if (in_array($envValue, ['Lax', 'Strict', 'None'], true)) {
            return $envValue;
        }

        return 'Lax';
    }

    /**
     * Read a config key without ever throwing.
     *
     * @param string $key Config key to read.
     * @return mixed Configured value or null when unavailable.
     */
    private static function readConfig(string $key): mixed
    {
        try {
            return Config::get($key);
        } catch (Throwable) {
            return null;
        }
    }
}
