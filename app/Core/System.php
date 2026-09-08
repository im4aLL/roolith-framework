<?php
namespace App\Core;

use App\Core\Exceptions\Exception;
use Psr\Log\LoggerInterface;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Route\Interfaces\RouterInterface;
use Roolith\Store\Interfaces\DatabaseInterface;
use Throwable;

/**
 * Application lifecycle: bootstrap, request handling, and teardown.
 *
 * Owns the per-request trace ID and logger, syncs the Env singleton into
 * Roolith Config, validates config shape, and connects the database.
 */
class System
{
    /**
     * Active database connection, null when running without a database.
     *
     * @var DatabaseInterface|null
     */
    protected ?DatabaseInterface $db;

    /**
     * Per-request trace ID correlating bootstrap and error log lines.
     *
     * @var string
     */
    protected string $traceId;

    /**
     * PSR-3 logger writing to the configured log path.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Build the app shell with an optional injected logger.
     *
     * Loads `.env`, legacy constants, and helpers, then creates a
     * trace ID and a file logger defaulting to LOG_PATH.
     *
     * Two-layer log wiring: Env is read directly via
     * Logger::defaultLogPath() and Logger::defaultLogEnabled() because
     * config validation has not run yet; config/config.php exposes the
     * same values as `logPath` and `logEnabled` for post-validation
     * consumers. Routine logs default to off (LOG_ENABLED unset or '0').
     *
     * @param LoggerInterface|null $logger Injected logger (tests) or null for the default file logger.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $basePath = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);

        Env::load($basePath);

        require_once $basePath . "/constant.php";

        $cmsConstantPath = $basePath . "/cms-constant.php";
        if (file_exists($cmsConstantPath)) {
            require_once $cmsConstantPath;
        }

        require_once $basePath . "/app/Utils/functions.php";

        // Re-apply timezone after Env::load() so a .env-only APP_TIMEZONE
        // (invisible to index.php before Env loads) still takes effect.
        // index.php also loads Env before its early read; this second call
        // covers workers/tests that construct System directly.
        try {
            Settings::applyDefaultTimezone();
        } catch (Throwable) {
            // Timezone must never break construction.
        }

        $this->db = null;
        try {
            $this->traceId = bin2hex(random_bytes(8));
        } catch (Throwable) {
            $this->traceId = uniqid('trace-', true);
        }
        $this->logger = $logger ?? new Logger(Logger::defaultLogPath(), $this->traceId, Logger::defaultLogEnabled());
        $this->registerCustomError();
    }

    /**
     * Get the PSR-3 logger for this request.
     *
     * @return LoggerInterface Active logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the per-request trace ID.
     *
     * @return string Trace ID correlating log lines for this request.
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * Bootstrap application.
     *
     * Preserves the exception chain on every failure via
     * new Exception(message, 0, previous) so file, line, and trace survive.
     * Config-missing (InvalidArgumentException from Config::get/validate)
     * and connect-failed (failure inside connectToDatabase) are kept
     * distinct with distinct messages and each logs exactly once.
     *
     * @return static Self for chaining.
     * @throws Exception When bootstrap fails, chained to the cause.
     */
    public function bootstrap(): static
    {
        $this->logger->info('bootstrap started', ['env' => Env::appEnv()]);

        try {
            // Bridge App Env (single source) into Roolith Config env.
            // ROOLITH_ENV already mirrors APP_ENV via constant.php, but an
            // explicit setEnv wins over any pre-existing ROOLITH_ENVIRONMENT
            // process env and makes the sync intentional. Verified against
            // vendor/roolith/config API: Config::setEnv(string):void exists.
            Config::setEnv(Env::appEnv());
        } catch (InvalidArgumentException $e) {
            $this->logger->error('bootstrap failed', ['error' => $e->getMessage()]);
            throw new Exception(
                "Invalid configuration: APP_ENV '" . Env::appEnv() . "' is not a valid Roolith env name. " .
                "Use letters, numbers, '-' or '_' (e.g. APP_ENV=production).",
                0,
                $e
            );
        }

        $this->logger->info('config env synced', ['env' => Env::appEnv()]);

        try {
            ConfigValidator::validate();
            $this->logger->info('config validated');

            // Re-apply timezone after config boots so Config `timezone`
            // wins over the early index.php default when set.
            try {
                Settings::applyDefaultTimezone();
            } catch (Throwable) {
                // Timezone must never break bootstrap.
            }

            Session::start();
            $this->sendSecurityHeaders();

            $this->preProcessor();
        } catch (InvalidArgumentException $e) {
            $this->logger->error('bootstrap failed', ['error' => $e->getMessage()]);
            throw new Exception($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            $this->logger->error('bootstrap failed', ['error' => $e->getMessage()]);
            throw new Exception($e->getMessage(), 0, $e);
        }

        try {
            $dbConfig = Config::get("database");
        } catch (InvalidArgumentException $e) {
            $this->logger->error('bootstrap failed', ['error' => $e->getMessage()]);
            throw new Exception("Invalid configuration: cannot read database config: " . $e->getMessage(), 0, $e);
        }

        try {
            $this->connectToDatabase($dbConfig);
        } catch (Exception $e) {
            $this->logger->error('database connect failed', ['error' => $e->getMessage()]);
            throw new Exception("Database connection failed: " . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            $this->logger->error('database connect failed', ['error' => $e->getMessage()]);
            throw new Exception("Database connection failed: " . $e->getMessage(), 0, $e);
        }

        $this->logger->info('bootstrap completed');

        return $this;
    }

    /**
     * Close application.
     *
     * @return static Self for chaining.
     */
    public function complete(): static
    {
        $this->disconnectFromDatabase();
        Storage::removeTemp();

        return $this;
    }

    /**
     * Process route
     *
     * Loads routes.php with require (not require_once, which returns 1 on
     * repeat calls) and verifies the RouterInterface contract so a bad
     * return fails fast with context instead of a TypeError.
     *
     * Re-entry guard: resets the shared RouterFactory singleton before
     * loading so a second processRequest() in the same process (tests,
     * workers) starts from an empty route table instead of appending the
     * same routes twice.
     *
     * @return static Self for chaining.
     * @throws Exception When routes.php does not return a RouterInterface.
     */
    public function processRequest(): static
    {
        RouterFactory::reset();

        $router = require APP_ROOT . "/app/Http/routes.php";

        if (!$router instanceof RouterInterface) {
            throw new Exception("Router bootstrap failed: app/Http/routes.php must return a RouterInterface.");
        }

        $this->router($router);

        return $this;
    }

    /**
     * Run the router for the current request.
     *
     * @param RouterInterface $router Active router.
     * @return static Self for chaining.
     */
    protected function router(RouterInterface $router): static
    {
        $router->run();

        return $this;
    }

    /**
     * Connect to database.
     *
     * Skips when the config is null (runs without a database). Preserves
     * the cause: a thrown driver error is chained, a false return becomes
     * a clear message with no previous (nothing to chain).
     *
     * @param array<string, mixed>|null $databaseConfig Validated database config (array) or null to skip.
     * @return static Self for chaining.
     * @throws Exception When the database connection fails, chained to the driver error when available.
     */
    protected function connectToDatabase(?array $databaseConfig): static
    {
        if ($databaseConfig === null || $databaseConfig === []) {
            return $this;
        }

        $this->db = DatabaseFactory::getInstance();

        try {
            $isConnected = $this->db->connect($databaseConfig);
        } catch (Throwable $e) {
            throw new Exception("Unable to connect to the database: " . $e->getMessage(), 0, $e);
        }

        if (!$isConnected) {
            throw new Exception("Unable to connect to the database");
        }

        return $this;
    }

    /**
     * Disconnect from the database when connected.
     *
     * @return static Self for chaining.
     */
    protected function disconnectFromDatabase(): static
    {
        $this->db?->disconnect();

        return $this;
    }

    /**
     * Pre processor
     *
     * Runs canonical host redirects with the request logger so dropped
     * hosts leave a trace in the same correlated log stream. forceNonWww=1
     * strips www (example www.site -> site); forceNonWww=0 adds www
     * (example site -> www.site). There is no "off" mode by design so one
     * canonical host always wins.
     *
     * @return void
     * @throws InvalidArgumentException When config lookup fails.
     */
    private function preProcessor(): void
    {
        if (Config::get("forceNonWww")) {
            PreProcessor::forceNonWww($this->logger);
        } else {
            PreProcessor::forceWww($this->logger);
        }
    }

    /**
     * Baseline security headers sent on every response.
     *
     * Conservative fail-closed values: scripts and frames stay
     * same-origin, MIME sniffing is off, referrers are limited, and HSTS
     * pins https for a year (browsers ignore it over plain http, and
     * sendSecurityHeaders() skips HSTS unless the request is https).
     * Disable with SECURITY_HEADERS=0 only when a reverse proxy already
     * sends equivalent headers. The CSP default-src 'self' blocks inline
     * scripts/styles and remote Vite dev servers; override or extend the
     * policy in the proxy or view layer when inline/Vite HMR is needed.
     *
     * @return array<string, string> Header name to value map.
     */
    public static function securityHeaders(): array
    {
        return [
            'Content-Security-Policy' => "default-src 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];
    }

    /**
     * Whether baseline security headers are enabled.
     *
     * Config `securityHeaders` wins when a bool; otherwise falls back to
     * Env `SECURITY_HEADERS` parsed with filter_var, then true, so
     * setting `.env` alone takes effect without a config key. Never
     * throws so a header failure can never break the request.
     *
     * @return bool True when baseline headers should be sent.
     */
    public static function isSecurityHeadersEnabled(): bool
    {
        try {
            $configured = Config::get('securityHeaders');

            if (is_bool($configured)) {
                return $configured;
            }
        } catch (Throwable) {
            // Fall through to Env fallback below.
        }

        try {
            $raw = Env::get('SECURITY_HEADERS');

            if ($raw === null) {
                return true;
            }

            return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Send baseline security headers unless disabled or already sent.
     *
     * Reads the securityHeaders flag via isSecurityHeadersEnabled()
     * (default on) and never throws so a header failure can never break
     * the request. HSTS is only sent when the current request is https;
     * over plain http it is skipped because browsers ignore it there.
     *
     * @return void
     */
    public function sendSecurityHeaders(): void
    {
        try {
            $enabled = self::isSecurityHeadersEnabled();
        } catch (Throwable) {
            $enabled = true;
        }

        if (!$enabled) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        try {
            $isHttps = PreProcessor::currentScheme() === 'https';

            foreach (self::securityHeaders() as $name => $value) {
                if ($name === 'Strict-Transport-Security' && !$isHttps) {
                    continue;
                }

                header($name . ': ' . $value);
            }
        } catch (\Throwable) {
            // Headers must never break the request.
        }
    }

    /**
     * Register custom error
     *
     * Fail-closed: Whoops only in development, otherwise hide details.
     *
     * @return void
     */
    private function registerCustomError(): void
    {
        if (Env::isDevelopment()) {
            $whoops = new \Whoops\Run();
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $whoops->register();

            return;
        }

        ini_set("display_errors", "0");
        ini_set("log_errors", "1");
        error_reporting(E_ALL);
    }
}
