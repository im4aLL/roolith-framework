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

        $logPath = Config::get('logPath');

        if (!is_string($logPath) || trim($logPath) === '') {
            throw new Exception(
                "Invalid configuration: 'logPath' must be a non-empty string. " .
                "Set LOG_PATH in .env or 'logPath' in config/config.php."
            );
        }

        if (str_contains($logPath, "\0")) {
            throw new Exception(
                "Invalid configuration: 'logPath' must not contain null bytes. " .
                "Set LOG_PATH in .env to a plain path without null bytes or `..` segments."
            );
        }

        $normalizedLogPath = str_replace('\\', '/', $logPath);

        if (preg_match('#(^|/)\.\.(/|$)#', $normalizedLogPath) === 1) {
            throw new Exception(
                "Invalid configuration: 'logPath' must not contain `..` traversal. " .
                "Set LOG_PATH in .env to a plain path without null bytes or `..` segments."
            );
        }

        $logEnabled = Config::get('logEnabled');

        if (!is_bool($logEnabled)) {
            throw new Exception(
                "Invalid configuration: 'logEnabled' must be a boolean. " .
                "Set LOG_ENABLED in .env (1/0) or 'logEnabled' in config/config.php."
            );
        }
    }
}
