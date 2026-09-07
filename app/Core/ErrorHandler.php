<?php
namespace App\Core;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Front-controller exception handling for uncaught throwables.
 *
 * Logs the failure with the per-request trace ID, rethrows in development
 * for Whoops/verbose output, otherwise hides details behind HTTP 500.
 */
final class ErrorHandler
{
    /**
     * Handle an uncaught throwable from the front controller.
     *
     * Logs with trace/class/file, rethrows in development, else sends
     * HTTP 500 with an escaped trace ID. Never throws for logging failures.
     *
     * @param System|null $app Booted app (null when boot failed before System existed).
     * @param Throwable $e Uncaught throwable.
     * @return void
     * @throws Throwable Rethrows $e unchanged when APP_ENV is exactly `development`.
     */
    public static function handle(?System $app, Throwable $e): void
    {
        $traceId = self::resolveTraceId($app);

        try {
            self::resolveLogger($app, $traceId)->error('unhandled exception', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
        } catch (Throwable) {
            // Logging must never mask the original failure.
        }

        if (Env::isDevelopment()) {
            throw $e;
        }

        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        if (!headers_sent()) {
            http_response_code(500);
        }

        echo 'Internal Server Error (trace: ' . htmlspecialchars($traceId, ENT_QUOTES, 'UTF-8') . ')';
    }

    /**
     * Resolve the trace ID for the current request.
     *
     * Reuses the System trace ID when available so error lines correlate
     * with bootstrap lines, otherwise generates a fresh one.
     *
     * @param System|null $app Booted app or null.
     * @return string Trace ID for the error response and log line.
     */
    public static function resolveTraceId(?System $app): string
    {
        if ($app instanceof System) {
            try {
                return $app->getTraceId();
            } catch (Throwable) {
                return self::generateTraceId();
            }
        }

        return self::generateTraceId();
    }

    /**
     * Generate a fresh trace ID with a non-crypto fallback.
     *
     * @return string Random hex ID, or a uniqid fallback when randomness fails.
     */
    public static function generateTraceId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable) {
            return uniqid('trace-', true);
        }
    }

    /**
     * Resolve the logger for error reporting.
     *
     * Reuses the System logger when available, otherwise builds a file
     * logger on the configured LOG_PATH default. The fallback honors the
     * LOG_ENABLED flag like System, but warning and above are always
     * written so the 500 trace still correlates in the log file.
     *
     * @param System|null $app Booted app or null.
     * @param string $traceId Trace ID for the fallback logger.
     * @return LoggerInterface Logger to record the unhandled exception.
     */
    public static function resolveLogger(?System $app, string $traceId): LoggerInterface
    {
        if ($app instanceof System) {
            return $app->getLogger();
        }

        return new Logger(Logger::defaultLogPath(), $traceId, Logger::defaultLogEnabled());
    }
}
