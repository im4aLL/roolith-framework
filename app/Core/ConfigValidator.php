<?php
namespace App\Core;

use App\Core\Exceptions\Exception;
use Roolith\Configuration\Config;

/**
 * Asserts the runtime config shape at bootstrap so misconfiguration fails
 * fast with a helpful message instead of late unclear errors.
 */
class ConfigValidator
{
    /**
     * Validate required config keys against their expected shapes.
     *
     * @return void
     * @throws Exception When any required key is missing or has the wrong shape.
     */
    public static function validate(): void
    {
        $baseUrl = Config::get('baseUrl');

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            throw new Exception(
                "Invalid configuration: 'baseUrl' is missing or empty. " .
                "Set 'baseUrl' in config/config.php or APP_URL in .env " .
                "(e.g. APP_URL=http://localhost:8080/)."
            );
        }

        if (filter_var(trim($baseUrl), FILTER_VALIDATE_URL) === false) {
            throw new Exception(
                "Invalid configuration: 'baseUrl' must be a valid URL. " .
                "Set 'baseUrl' in config/config.php or APP_URL in .env " .
                "(e.g. APP_URL=http://localhost:8080/)."
            );
        }

        $baseParts = parse_url(trim($baseUrl));
        $baseScheme = strtolower((string) ($baseParts['scheme'] ?? ''));

        if ($baseScheme !== 'http' && $baseScheme !== 'https') {
            throw new Exception(
                "Invalid configuration: 'baseUrl' scheme must be http or https. " .
                "Set 'baseUrl' in config/config.php or APP_URL in .env " .
                "(e.g. APP_URL=http://localhost:8080/)."
            );
        }

        $database = Config::get('database');

        if ($database !== null && !is_array($database)) {
            throw new Exception(
                "Invalid configuration: 'database' must be an array or null. " .
                "Set DB_HOST/DB_NAME in .env, or leave them empty to run without a database."
            );
        }

        $version = Config::get('version');

        if (!is_string($version) && !is_int($version) && !is_float($version)) {
            throw new Exception(
                "Invalid configuration: 'version' must be a string or number. " .
                "Set APP_VERSION in .env or 'version' in config/config.php."
            );
        }

        if (is_string($version) && trim($version) === '') {
            throw new Exception(
                "Invalid configuration: 'version' must not be an empty string. " .
                "Set APP_VERSION in .env or 'version' in config/config.php."
            );
        }

        $forceNonWww = Config::get('forceNonWww');

        if (!is_bool($forceNonWww)) {
            throw new Exception(
                "Invalid configuration: 'forceNonWww' must be a boolean. " .
                "Set FORCE_NON_WWW in .env (1/0) or 'forceNonWww' in config/config.php."
            );
        }

        self::validateOptionalLoggingConfig();
        self::validateOptionalCookieConfig();
        self::validateOptionalProxyConfig();
        self::validateOptionalSecurityHeadersConfig();
    }

    /**
     * Validate optional logging keys when present.
     *
     * Missing (null) values are skipped so the minimal config.php stays valid;
     * defaults live in Logger::defaultLogPath() and Logger::defaultLogEnabled().
     * Present values must match their expected shapes.
     *
     * @return void
     * @throws Exception When a present logging key has the wrong shape.
     */
    private static function validateOptionalLoggingConfig(): void
    {
        $logPath = Config::get('logPath');

        if ($logPath === null) {
            // Optional: fall back to Logger::defaultLogPath().
        } elseif (!is_string($logPath) || trim($logPath) === '') {
            throw new Exception(
                "Invalid configuration: 'logPath' must be a non-empty string. " .
                "Set LOG_PATH in .env or 'logPath' in config/config.php."
            );
        } elseif (str_contains($logPath, "\0")) {
            throw new Exception(
                "Invalid configuration: 'logPath' must not contain null bytes. " .
                "Set LOG_PATH in .env to a plain path without null bytes or `..` segments."
            );
        } else {
            $normalizedLogPath = str_replace('\\', '/', $logPath);

            if (preg_match('#(^|/)\.\.(/|$)#', $normalizedLogPath) === 1) {
                throw new Exception(
                    "Invalid configuration: 'logPath' must not contain `..` traversal. " .
                    "Set LOG_PATH in .env to a plain path without null bytes or `..` segments."
                );
            }
        }

        $logEnabled = Config::get('logEnabled');

        if ($logEnabled !== null && !is_bool($logEnabled)) {
            throw new Exception(
                "Invalid configuration: 'logEnabled' must be a boolean. " .
                "Set LOG_ENABLED in .env (1/0) or 'logEnabled' in config/config.php."
            );
        }
    }

    /**
     * Validate optional cookie and session keys when present.
     *
     * Missing (null) values are skipped so pre-existing config seeds keep
     * working; present values must match their expected shapes.
     *
     * @return void
     * @throws Exception When a present cookie key has the wrong shape.
     */
    private static function validateOptionalCookieConfig(): void
    {
        $cookiePath = Config::get('cookiePath');

        if ($cookiePath !== null && (!is_string($cookiePath) || trim($cookiePath) === '')) {
            throw new Exception(
                "Invalid configuration: 'cookiePath' must be a non-empty string. " .
                "Set COOKIE_PATH in .env or 'cookiePath' in config/config.php."
            );
        }

        $cookieDomain = Config::get('cookieDomain');

        if ($cookieDomain !== null && !is_string($cookieDomain)) {
            throw new Exception(
                "Invalid configuration: 'cookieDomain' must be a string. " .
                "Set COOKIE_DOMAIN in .env or 'cookieDomain' in config/config.php."
            );
        }

        $cookieSecure = Config::get('cookieSecure');

        if ($cookieSecure !== null && !is_bool($cookieSecure)) {
            throw new Exception(
                "Invalid configuration: 'cookieSecure' must be a boolean. " .
                "Set COOKIE_SECURE in .env (1/0) or 'cookieSecure' in config/config.php."
            );
        }

        $cookieSameSite = Config::get('cookieSameSite');

        if ($cookieSameSite !== null && !in_array($cookieSameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new Exception(
                "Invalid configuration: 'cookieSameSite' must be one of Lax, Strict, None. " .
                "Set COOKIE_SAMESITE in .env or 'cookieSameSite' in config/config.php."
            );
        }

        if ($cookieSameSite === 'None' && $cookieSecure !== true) {
            throw new Exception(
                "Invalid configuration: 'cookieSameSite=None' requires 'cookieSecure=true' (browsers reject None without Secure). " .
                "Set COOKIE_SECURE=1 in .env or 'cookieSecure' in config/config.php."
            );
        }

        $sessionLifetime = Config::get('sessionLifetime');

        if ($sessionLifetime !== null && (!is_int($sessionLifetime) || $sessionLifetime < 0)) {
            throw new Exception(
                "Invalid configuration: 'sessionLifetime' must be an int >= 0. " .
                "Set SESSION_LIFETIME in .env or 'sessionLifetime' in config/config.php."
            );
        }
    }

    /**
     * Validate the optional trusted-proxies list when present.
     *
     * @return void
     * @throws Exception When trustedProxies is present but not a string list.
     */
    private static function validateOptionalProxyConfig(): void
    {
        $trustedProxies = Config::get('trustedProxies');

        if ($trustedProxies === null) {
            return;
        }

        if (!is_array($trustedProxies)) {
            throw new Exception(
                "Invalid configuration: 'trustedProxies' must be an array of IP strings. " .
                "Set TRUSTED_PROXIES in .env (comma-separated) or 'trustedProxies' in config/config.php."
            );
        }

        foreach ($trustedProxies as $proxy) {
            if (!is_string($proxy) || trim($proxy) === '') {
                throw new Exception(
                    "Invalid configuration: 'trustedProxies' must contain non-empty IP strings. " .
                    "Set TRUSTED_PROXIES in .env (comma-separated) or 'trustedProxies' in config/config.php."
                );
            }
        }
    }

    /**
     * Validate the optional security-headers flag when present.
     *
     * @return void
     * @throws Exception When securityHeaders is present but not a boolean.
     */
    private static function validateOptionalSecurityHeadersConfig(): void
    {
        $securityHeaders = Config::get('securityHeaders');

        if ($securityHeaders !== null && !is_bool($securityHeaders)) {
            throw new Exception(
                "Invalid configuration: 'securityHeaders' must be a boolean. " .
                "Set SECURITY_HEADERS in .env (1/0) or 'securityHeaders' in config/config.php."
            );
        }
    }
}
