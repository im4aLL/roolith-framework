<?php
namespace App\Support;

use App\Core\Env;
use Throwable;

/**
 * CLI-aware debug dump helper.
 *
 * Backs the global p() function. Web output is wrapped in pre tags with
 * escaped content so dumped markup cannot break the page. CLI output is
 * plain text with no HTML wrapper. The exit flag only terminates in
 * development so production can never truncate the response.
 */
final class Debug
{
    /**
     * Format a value for debug output without echoing.
     *
     * Captures print_r output, escapes it for web SAPIs, and leaves it
     * plain for CLI. Never throws; falls back to a short type string.
     *
     * @param mixed $value Value to format.
     * @return string Formatted debug string.
     */
    public static function format(mixed $value): string
    {
        try {
            $dumped = print_r($value, true);
        } catch (Throwable) {
            $dumped = get_debug_type($value);
        }

        if (!is_string($dumped)) {
            $dumped = get_debug_type($value);
        }

        if (self::isCli()) {
            return $dumped . PHP_EOL;
        }

        return '<pre>' . Html::escape($dumped) . '</pre>';
    }

    /**
     * Print a value for debugging.
     *
     * Echoes format() output. The exit flag terminates only in
     * development (explicit APP_ENV=development); elsewhere it is
     * ignored so System::complete() always runs.
     *
     * @param mixed $value Value to print.
     * @param bool $exit Terminate after printing, dev environments only.
     * @return void
     */
    public static function dump(mixed $value, bool $exit = false): void
    {
        echo self::format($value);

        if ($exit && Env::isDevelopment()) {
            exit(0);
        }
    }

    /**
     * Check whether the current SAPI is CLI.
     *
     * Centralizes the php_sapi_name check so tests can reason about
     * one seam. Returns true for cli and phpdbg.
     *
     * @return bool True when running under CLI, false for web SAPIs.
     */
    public static function isCli(): bool
    {
        $sapi = php_sapi_name();

        return $sapi === 'cli' || $sapi === 'phpdbg';
    }
}
