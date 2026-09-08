<?php
namespace App\Core;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Shared PSR-3 log holder for static call sites.
 *
 * System owns the per-request logger instance; this static holder
 * mirrors it so helpers without DI (Url, Request, Controller, route
 * validation) can log on the same correlated stream. All methods
 * never throw so logging can never break the request.
 */
final class Log
{
    /**
     * Active PSR-3 logger, null before System boots or after reset.
     *
     * @var LoggerInterface|null
     */
    private static ?LoggerInterface $logger = null;

    /**
     * Set the shared logger for the current request.
     *
     * Called by System::__construct so every later static log() call
     * correlates with the request trace ID. Overwrites any previous
     * logger; the latest System instance wins.
     *
     * @param LoggerInterface $logger Active PSR-3 logger.
     * @return void
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Get the shared logger when booted.
     *
     * @return LoggerInterface|null Active logger or null when unbooted.
     */
    public static function getLogger(): ?LoggerInterface
    {
        return self::$logger;
    }

    /**
     * Clear the shared logger (test seam).
     *
     * Drops the static reference so tests start without a logger and
     * cannot leak log state across tests.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$logger = null;
    }

    /**
     * Log at debug level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    /**
     * Log at info level without ever throwing.
     *
     * Falls back to error_log when no logger is set so early-boot
     * messages still leave a trail.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * Log at notice level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        self::write('notice', $message, $context);
    }

    /**
     * Log at warning level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * Log at error level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * Log at critical level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::write('critical', $message, $context);
    }

    /**
     * Log at alert level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        self::write('alert', $message, $context);
    }

    /**
     * Log at emergency level without ever throwing.
     *
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::write('emergency', $message, $context);
    }

    /**
     * Log at an arbitrary PSR-3 level without ever throwing.
     *
     * @param string $level PSR-3 level name (debug, info, notice, warning, error, critical, alert, emergency).
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data appended as JSON by the file logger.
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        self::write($level, $message, $context);
    }

    /**
     * Write a log line via the shared logger or error_log fallback.
     *
     * Never throws: logger failures fall back to error_log, and even
     * the fallback is guarded so logging stays side-effect free.
     *
     * @param string $level PSR-3 level name.
     * @param string $message Log message.
     * @param array<string, mixed> $context Extra data.
     * @return void
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        $logger = self::$logger;

        if ($logger instanceof LoggerInterface) {
            try {
                $logger->log($level, $message, $context);

                return;
            } catch (Throwable) {
                // Fall through to error_log below.
            }
        }

        try {
            $suffix = $context === [] ? '' : ' ' . substr(str_replace(["\r", "\n"], ' ', (string) json_encode($context)), 0, 500);
            error_log('[Roolith ' . $level . '] ' . str_replace(["\r", "\n"], ' ', $message) . $suffix);
        } catch (Throwable) {
            // Logging must never throw.
        }
    }
}
