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
     * Whether complete() already ran for this instance.
     *
     * @var bool
     */
    protected bool $completed = false;

    /**
     * Whether the shutdown fallback was registered for this process.
     *
     * @var bool
     */
    private static bool $shutdownRegistered = false;

    /**
     * Last constructed System for the shutdown fallback.
     *
     * @var static|null
     */
    private static ?System $shutdownInstance = null;

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
     * Registers a shutdown fallback so complete() (disconnect plus temp
     * cleanup) still runs even when code calls exit/die outside the
     * Response flow.
     *
     * @param LoggerInterface|null $logger Injected logger (tests) or null for the default file logger.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $basePath = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);

        Env::load($basePath);

        require_once $basePath . "/constant.php";

        // Legacy seam: optional cms-constant.php from the CMS release
        // asset (see docs/cms-installer.md). Gated on the APP_ENABLE_CMS
        // opt-in flag like routes.php and functions.php so CMS constants
        // never load when core-only mode is on (APP_ENABLE_CMS=0).
        // installer.zip stays tracked locally for reference and local
        // install but is omitted from dist via archive.exclude plus
        // export-ignore plus dockerignore and hidden over HTTP via
        // .htaccess 404.
        $cmsConstantPath = $basePath . "/cms-constant.php";
        if (defined('APP_ENABLE_CMS') && APP_ENABLE_CMS && is_file($cmsConstantPath) && is_readable($cmsConstantPath)) {
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
        Log::setLogger($this->logger);
        $this->registerCustomError();
        $this->registerShutdownFallback();
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
     * Idempotent: safe to call twice (explicit complete() plus the shutdown
     * fallback). Never throws so shutdown and error paths stay safe.
     *
     * @return static Self for chaining.
     */
    public function complete(): static
    {
        if ($this->completed) {
            return $this;
        }

        $this->completed = true;

        try {
            $this->disconnectFromDatabase();
        } catch (Throwable) {
            // Cleanup must never throw.
        }

        try {
            Storage::removeTemp();
        } catch (Throwable) {
            // Cleanup must never throw.
        }

        return $this;
    }

    /**
     * Boot and serve the current HTTP request (front-controller entry).
     *
     * Runs bootstrap, request processing, and teardown; redirects are
     * emitted then completed, and any other failure goes to ErrorHandler.
     * Nothing here throws, so index.php stays a thin bootstrap.
     *
     * @return void
     */
    public static function run(): void
    {
        try {
            $app = new self();
            $app->bootstrap()
                ->processRequest()
                ->complete();
        } catch (Exceptions\RedirectException $e) {
            try {
                ($app ?? null)?->emit($e->getResponse());
            } catch (Throwable) {
                // Emission must never mask the redirect.
            } finally {
                try {
                    ($app ?? null)?->complete();
                } catch (Throwable) {
                    // Cleanup must never throw.
                }
            }
        } catch (Throwable $e) {
            ErrorHandler::handle($app ?? null, $e);
        }
    }

    /**
     * Reset completion state for tests.
     *
     * Lets a reused System instance complete again after an explicit
     * complete() call within the same test process.
     *
     * @return void
     */
    public function resetCompletionForTests(): void
    {
        $this->completed = false;
    }

    /**
     * Reset static shutdown state for tests.
     *
     * Clears the registered flag and instance so each test can assert
     * fresh registration without order dependence. L8 accepted: the real
     * PHP shutdown handler cannot be unregistered, so reset only affects
     * the test-visible flag plus instance; at most one real handler ever
     * exists per process and it is benign.
     *
     * @return void
     */
    public static function resetShutdownForTests(): void
    {
        self::$shutdownRegistered = false;
        self::$shutdownInstance = null;
    }

    /**
     * Emit an immutable response (status, headers, body).
     *
     * Thin proxy over Response::send() so controllers and the front
     * controller share one emission path.
     *
     * @param Response $response Response to emit.
     * @return void
     */
    public function emit(Response $response): void
    {
        $response->send();
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
     * Route handlers using the string Controller@method form are
     * validated via RouteValidator (class_exists plus method_exists) and
     * failures are logged with context; the vendor router still owns
     * runtime dispatch so behavior stays identical.
     *
     * @return static Self for chaining.
     * @throws Exception When routes.php does not return a RouterInterface.
     */
    public function processRequest(): static
    {
        RouterFactory::reset();

        $router = require APP_ROOT . "/app/Http/routes.php";

        if (!$router instanceof RouterInterface) {
            $this->logger->error('router bootstrap failed', ['error' => 'routes.php must return RouterInterface']);

            throw new Exception("Router bootstrap failed: app/Http/routes.php must return a RouterInterface.");
        }

        $this->assertRouteHandlers($router);
        $this->router($router);

        return $this;
    }

    /**
     * Assert string-based Controller@method handlers resolve.
     *
     * Logs every invalid string handler with route context so typos
     * surface in the correlated log stream instead of only as a vendor
     * 404 or 500 at request time. Never throws so a lint failure can
     * never break the request; the vendor router still handles the
     * runtime response.
     *
     * @param object $router Active router (RouterInterface plus optional getRouteList).
     * @return void
     */
    public function assertRouteHandlers(object $router): void
    {
        try {
            if (!method_exists($router, 'getRouteList')) {
                return;
            }

            $routes = $router->getRouteList();
        } catch (Throwable $e) {
            $this->logger->warning('route list unavailable', ['error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 200)]);

            return;
        }

        if (!is_array($routes)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $routeList */
        $routeList = $routes;
        $errors = RouteValidator::validateRouteList($routeList);

        foreach ($errors as $error) {
            $this->logger->warning('invalid route handler', ['error' => substr(str_replace(["\r", "\n"], ' ', $error), 0, 500)]);
        }

        if ($errors !== []) {
            $this->logger->info('route validation completed with errors', ['count' => count($errors)]);
        } else {
            $this->logger->info('route validation completed', ['count' => count($routeList)]);
        }
    }

    /**
     * Run the router for the current request.
     *
     * Logs the dispatch start plus method and URI, then logs 404
     * outcomes (no matched route renders views/404.php) and any
     * dispatch throwable so router and 404 error paths share the
     * correlated PSR-3 stream with bootstrap and controller errors.
     * Throwables bubble to ErrorHandler so the 500 contract is
     * unchanged.
     *
     * @param RouterInterface $router Active router.
     * @return static Self for chaining.
     */
    protected function router(RouterInterface $router): static
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $this->logger->info('router dispatch started', ['method' => $method, 'uri' => substr($uri, 0, 500)]);

        try {
            $router->run();
        } catch (Throwable $e) {
            $this->logger->error('router dispatch failed', [
                'error' => substr(str_replace(["\r", "\n"], ' ', $e->getMessage()), 0, 500),
                'class' => get_class($e),
                'method' => $method,
                'uri' => substr($uri, 0, 500),
            ]);

            throw $e;
        }

        try {
            $code = http_response_code();

            if ($code === 404) {
                $this->logger->warning('route not found', ['method' => $method, 'uri' => substr($uri, 0, 500)]);
            } else {
                $this->logger->info('router dispatch completed', ['code' => $code === false ? 200 : $code]);
            }
        } catch (Throwable) {
            // Logging must never break dispatch.
        }

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
     * No-exit: PreProcessor returns a Response instead of exiting. When a
     * redirect is needed this throws RedirectException carrying the
     * response so the front controller emits it and still runs complete().
     *
     * @return void
     * @throws Exceptions\RedirectException When a canonical redirect is needed.
     * @throws InvalidArgumentException When config lookup fails.
     */
    private function preProcessor(): void
    {
        $pending = null;

        if (Config::get("forceNonWww")) {
            $pending = PreProcessor::forceNonWww($this->logger);
        } else {
            $pending = PreProcessor::forceWww($this->logger);
        }

        if ($pending instanceof Response) {
            throw new Exceptions\RedirectException($pending->header('Location') ?? '/', $pending->status());
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

    /**
     * Register the shutdown fallback ensuring complete() always runs.
     *
     * Even when code calls exit/die outside the Response flow, PHP still
     * runs shutdown functions. The fallback calls complete() idempotently
     * (disconnect plus temp cleanup) and never throws. The latest System
     * instance wins so tests constructing many instances do not leak old
     * connections into shutdown.
     *
     * L8 accepted: PHP cannot unregister shutdown functions, so only one
     * handler is ever registered per process (guarded by
     * $shutdownRegistered); re-construction only swaps the instance.
     *
     * @return void
     */
    private function registerShutdownFallback(): void
    {
        self::$shutdownInstance = $this;

        if (self::$shutdownRegistered) {
            return;
        }

        self::$shutdownRegistered = true;

        register_shutdown_function(static function (): void {
            try {
                self::$shutdownInstance?->complete();
            } catch (Throwable) {
                // Shutdown must never throw.
            }
        });
    }

    /**
     * Whether the shutdown fallback is registered (test seam).
     *
     * @return bool True after any System construction in this process.
     */
    public static function isShutdownFallbackRegistered(): bool
    {
        return self::$shutdownRegistered;
    }
}
