<?php
namespace App\Core;


use Carbon\Carbon;
use Roolith\Configuration\Config;
use Throwable;

class Storage
{
    /**
     * Session key holding one-request flash data.
     *
     * @var string
     */
    protected static string $tempKey = '_temp_';

    /**
     * Set cookie with hardened defaults.
     *
     * Uses the options array so path, Secure, HttpOnly, and SameSite are
     * always explicit. Values are cast to string and left for setcookie()
     * to encode (no manual urlencode, which would double-encode).
     *
     * @param string $name Cookie name.
     * @param mixed $value Cookie value cast to string.
     * @param Carbon $expiration Expiry moment.
     * @return bool True when the cookie header was queued.
     */
    public static function setCookie(string $name, mixed $value, Carbon $expiration): bool
    {
        return setcookie($name, (string) $value, self::cookieOptions($expiration->getTimestamp()));
    }

    /**
     * Delete cookie using the same path and domain it was set with.
     *
     * Matching path/domain is required for browsers to actually clear the
     * cookie. The superglobal is unset so same-request reads see the deletion.
     *
     * @param string $name Cookie name.
     * @return bool True when the expiry header was queued.
     */
    public static function deleteCookie(string $name): bool
    {
        $result = setcookie($name, '', self::cookieOptions(time() - 3600));

        unset($_COOKIE[$name]);

        return $result;
    }

    /**
     * Build hardened cookie options shared by set and delete.
     *
     * Pure and testable: no headers are sent. Each key reads Config
     * first, then Env (COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE,
     * COOKIE_SAMESITE), then a fail-closed default, so `.env` alone
     * takes effect without a config key.
     *
     * @param int $expires Unix timestamp for expiry.
     * @return array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string} Options for setcookie().
     */
    public static function cookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => self::configString('cookiePath', '/'),
            'domain' => self::configString('cookieDomain', ''),
            'secure' => self::configBool('cookieSecure', Session::defaultSecure()),
            'httponly' => true,
            'samesite' => self::configSameSite(),
        ];
    }

    /**
     * Get cookie by name
     *
     * @param string $name Cookie name.
     * @return mixed Cookie value or null when missing.
     */
    public static function getCookie(string $name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Set session value, starting the session once via Session helper.
     *
     * Returns the Session::start() result so callers can detect a failed
     * startup; the value is only stored when the session is active.
     *
     * @param string $name Session key.
     * @param mixed $value Session value.
     * @return bool True when stored, false when the session failed to start.
     */
    public static function setSession(string $name, mixed $value): bool
    {
        $started = Session::start();

        if (!$started) {
            return false;
        }

        $_SESSION[$name] = $value;

        return true;
    }

    /**
     * Get session value by name
     *
     * Returns false when the key is missing. A stored null also reads as
     * false (null coalescing) for BC; store a non-null sentinel when you
     * must distinguish missing from null.
     *
     * @param string $name Session key.
     * @return mixed Session value or false when missing (including stored null).
     */
    public static function getSession(string $name): mixed
    {
        return $_SESSION[$name] ?? false;
    }

    /**
     * Has session
     *
     * @param string $name Session key.
     * @return boolean True when the key exists.
     */
    public static function hasSession(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Delete session
     *
     * @param string $name Session key.
     * @return bool True when a value was removed.
     */
    public static function deleteSession(string $name): bool
    {
        if (self::hasSession($name)) {
            unset($_SESSION[$name]);

            return true;
        }

        return false;
    }

    /**
     * Set session for once
     *
     * @param string $name Flash key.
     * @param mixed $value Flash value.
     * @return boolean Always true.
     */
    public static function temp(string $name, mixed $value): bool
    {
        if (!self::hasSession(self::$tempKey)) {
            self::setSession(self::$tempKey, []);
        }

        $_SESSION[self::$tempKey][$name] = $value;

        return true;
    }

    /**
     * Remove temp data
     *
     * @return boolean True when flash data was removed.
     */
    public static function removeTemp(): bool
    {
        return self::deleteSession(self::$tempKey);
    }

    /**
     * Get temp data by name
     *
     * @param string $name Flash key.
     * @return mixed Flash value or false when missing.
     */
    public static function getTemp(string $name): mixed
    {
        if (!self::hasSession(self::$tempKey)) {
            return false;
        }

        if (isset($_SESSION[self::$tempKey][$name])) {
            return $_SESSION[self::$tempKey][$name];
        }

        return false;
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
        try {
            $value = Config::get($key);
        } catch (Throwable) {
            $value = null;
        }

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
        try {
            $value = Config::get($key);
        } catch (Throwable) {
            $value = null;
        }

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
        try {
            $value = Config::get('cookieSameSite');
        } catch (Throwable) {
            $value = null;
        }

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
     * Read an Env var without ever throwing.
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
}
