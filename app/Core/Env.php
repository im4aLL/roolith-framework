<?php
namespace App\Core;

/**
 * Single source for runtime environment.
 *
 * Single key `APP_ENV` with a fail-closed `production` default. Any APP_ENV
 * value is allowed (for example development, staging, uat, production);
 * Config::setEnv() already validates the charset. Only exactly `development`
 * boots with Whoops/verbose behavior, everything else boots
 * production-safe (no Whoops, no display_errors).
 *
 * `.env` loading is intentionally dependency-free: the supported syntax is
 * `KEY=VALUE` lines with `#` comments and optional single/double quotes. Env
 * vars already present in the process take precedence over the `.env` file.
 * If richer parsing (expansion, multiline) is ever needed, replace this with
 * vlucas/phpdotenv instead of growing a custom parser.
 */
class Env
{
    /**
     * Env key holding the active environment name.
     *
     * @var string
     */
    public const APP_ENV_KEY = 'APP_ENV';

    /**
     * Fallback environment when APP_ENV is unset or blank (fail-closed).
     *
     * @var string
     */
    public const DEFAULT_ENV = 'production';

    /**
     * The only environment that boots with verbose error output.
     *
     * @var string
     */
    public const DEVELOPMENT = 'development';

    /**
     * Load APP_ROOT/.env into $_ENV/$_SERVER/getenv without overwriting
     * non-empty values (empty/whitespace counts as unset, "0" preserved).
     *
     * @param string $basePath Project root containing the `.env` file.
     * @return void
     */
    public static function load(string $basePath): void
    {
        $file = rtrim($basePath, "/\\") . '/.env';

        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $pos = strpos($line, '=');

            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $raw = trim(substr($line, $pos + 1));

            if ($key === '' || !preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $key)) {
                continue;
            }

            $value = self::parseValue($raw);

            if (array_key_exists($key, $_ENV) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
                continue;
            }

            $existing = getenv($key);

            if (is_string($existing) && trim($existing) !== '') {
                $_ENV[$key] = $existing;

                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    /**
     * Read any env key with a default.
     *
     * Empty or whitespace-only string counts as unset (after trim);
     * "0" is preserved.
     *
     * @param string $key Env key to read.
     * @param string|null $default Value returned when the key is unset or blank.
     * @return string|null Trimmed env value or the default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = null;

        if (array_key_exists($key, $_ENV) && is_string($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER) && is_string($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } else {
            $fromEnv = getenv($key);
            $value = is_string($fromEnv) ? $fromEnv : null;
        }

        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }

    /**
     * Get the active app environment.
     *
     * Fail-closed: unset means production. Any non-empty APP_ENV value is
     * returned as-is (staging, uat, and other names are allowed).
     *
     * @return string Active environment name.
     */
    public static function appEnv(): string
    {
        return self::get(self::APP_ENV_KEY, self::DEFAULT_ENV) ?? self::DEFAULT_ENV;
    }

    /**
     * Check whether the active environment is exactly `development`.
     *
     * Only this value enables verbose error output; all other values
     * (including staging, uat, production) boot production-safe.
     *
     * @return bool True when APP_ENV is exactly `development`.
     */
    public static function isDevelopment(): bool
    {
        return self::appEnv() === self::DEVELOPMENT;
    }

    /**
     * Check whether the active environment boots production-safe.
     *
     * This is the inverse of isDevelopment(): any environment that is not
     * exactly `development` counts as production-safe.
     *
     * @return bool True for every environment except `development`.
     */
    public static function isProduction(): bool
    {
        return !self::isDevelopment();
    }

    /**
     * Check whether the active environment matches any of the given names.
     *
     * Generic helper for arbitrary environments (for example
     * `Env::is('staging', 'uat')`) without adding a named helper per env.
     *
     * @param string ...$envs Environment names to match against.
     * @return bool True when the active env equals one of the given names.
     */
    public static function is(string ...$envs): bool
    {
        return in_array(self::appEnv(), $envs, true);
    }

    /**
     * Parse a raw `.env` value.
     *
     * Strips matching single/double quotes, otherwise trims an inline
     * ` #` comment. A value starting with `#` yields an empty string.
     *
     * @param string $raw Raw value text after the `=` sign.
     * @return string Parsed value.
     */
    private static function parseValue(string $raw): string
    {
        if (strlen($raw) >= 2) {
            $first = $raw[0];
            $last = $raw[strlen($raw) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($raw, 1, -1);
            }
        }

        $hashPos = strpos($raw, ' #');

        if ($hashPos !== false) {
            return trim(substr($raw, 0, $hashPos));
        }

        if (str_starts_with($raw, '#')) {
            return '';
        }

        return $raw;
    }
}
