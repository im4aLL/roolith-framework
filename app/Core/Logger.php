<?php
namespace App\Core;

use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

/**
 * Minimal PSR-3 file logger.
 *
 * Bootstrap and exception-handler scope only. Each line carries the
 * per-request trace ID so bootstrap entries correlate with error entries.
 *
 * Never throws: directory creation and write failures fall back to error_log().
 */
class Logger extends AbstractLogger
{
    /**
     * Create a file logger for a trace ID.
     *
     * When disabled, routine levels (debug, info, notice) are dropped and
     * warning and above (warning, error, critical, alert, emergency) are
     * still written so crash visibility is never lost. Unknown levels are
     * written (fail-open).
     *
     * @param string $logFile Absolute path of the log file.
     * @param string $traceId Per-request trace ID written on every line.
     * @param bool $enabled False to drop routine levels, true to write all levels.
     */
    public function __construct(
        private string $logFile,
        private string $traceId,
        private bool $enabled = true
    ) {}

    /**
     * Resolve the default log file path.
     *
     * Env-only on purpose: the Logger is constructed in System::__construct
     * before config validation runs, so Config::get('logPath') is not
     * available yet. config/config.php exposes the same LOG_PATH value as
     * `logPath` for post-validation consumers; both default to
     * APP_ROOT/storage/logs/app.log.
     *
     * Allowed LOG_PATH values: non-empty path without null bytes or `..`
     * traversal segments; `\` separators are normalized to `/`. Relative
     * paths resolve against APP_ROOT. Invalid overrides fall back to the
     * default so the logger never throws.
     *
     * @return string Absolute log file path.
     */
    public static function defaultLogPath(): string
    {
        $basePath = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);

        $default = rtrim($basePath, "/\\") . '/storage/logs/app.log';

        $override = Env::get('LOG_PATH');

        if ($override === null) {
            return $default;
        }

        if (str_contains($override, "\0")) {
            return $default;
        }

        $normalized = str_replace('\\', '/', $override);

        if (preg_match('#(^|/)\.\.(/|$)#', $normalized) === 1) {
            return $default;
        }

        if ($normalized !== '' && !str_starts_with($normalized, '/') && preg_match('#^[A-Za-z]:/#', $normalized) !== 1) {
            return rtrim($basePath, "/\\") . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    /**
     * Resolve whether routine logs are enabled from the environment.
     *
     * Env-only on purpose: the Logger is constructed in System::__construct
     * before config validation runs, so Config::get('logEnabled') is not
     * available yet. config/config.php exposes the same LOG_ENABLED value as
     * `logEnabled` for post-validation consumers; both default to false
     * (unset or '0' means disabled).
     *
     * @return bool True when LOG_ENABLED is truthy, false by default.
     */
    public static function defaultLogEnabled(): bool
    {
        return filter_var(Env::get('LOG_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Log a message at the given level without ever throwing.
     *
     * @param mixed $level PSR-3 log level.
     * @param string|Stringable $message Log message (newlines are flattened).
     * @param array<string, mixed> $context Extra data appended as JSON.
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $fallbackLine = '';
        try {
            if (!$this->enabled && self::isRoutineLevel($level)) {
                return;
            }

            $sanitize = static fn ($value): string => str_replace(["\r", "\n"], ' ', (string) $value);

            $time = date('Y-m-d H:i:s');
            $traceId = $sanitize($this->traceId);
            $levelName = $sanitize(strtoupper((string) $level));
            $text = $sanitize($message);

            $contextStr = '';

            if ($context !== []) {
                $encoded = json_encode($context, JSON_UNESCAPED_SLASHES);

                if (!is_string($encoded)) {
                    $encoded = json_encode(['context' => '[unencodable context]'], JSON_UNESCAPED_SLASHES);
                }

                if (!is_string($encoded)) {
                    $encoded = '{"context":"[unencodable context]"}';
                }

                $contextStr = $sanitize($encoded);
            }

            $line = sprintf(
                "[%s] [%s] [%s] %s %s\n",
                $time,
                $traceId,
                $levelName,
                $text,
                $contextStr
            );

            $fallbackLine = $line;

            $dir = dirname($this->logFile);

            if (!is_dir($dir)) {
                // race-safe: @ suppresses the TOCTOU race warning between is_dir check and mkdir, failure still handled by return check + error_log fallback.
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    error_log('Logger fallback: ' . $line);

                    return;
                }
            }

            $written = file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

            if ($written === false) {
                error_log('Logger fallback: ' . $line);
            }
        } catch (Throwable) {
            try {
                $fallback = $fallbackLine !== ''
                    ? $fallbackLine
                    : str_replace(["\r", "\n"], ' ', (string) $message);
                error_log('Logger fallback: ' . substr($fallback, 0, 500));
            } catch (Throwable) {
                // Never throw from the logger.
            }
        }
    }

    /**
     * Get the per-request trace ID for this logger.
     *
     * @return string Trace ID passed to the constructor.
     */
    public function traceId(): string
    {
        return $this->traceId;
    }

    /**
     * Check whether a level is routine output dropped when logging is disabled.
     *
     * Routine levels are debug, info, and notice (case-insensitive).
     * Warning and above, plus unknown levels, return false so they are
     * always written and crash visibility is never lost.
     *
     * @param mixed $level PSR-3 log level to classify.
     * @return bool True for routine levels, false otherwise.
     */
    private static function isRoutineLevel(mixed $level): bool
    {
        try {
            $normalized = strtolower((string) $level);
        } catch (Throwable) {
            return false;
        }

        return in_array($normalized, ['debug', 'info', 'notice'], true);
    }
}
