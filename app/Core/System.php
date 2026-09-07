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
     * Bootstrap application
     *
     * @return $this
     * @throws Exception
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

            try {
                $this->connectToDatabase($dbConfig);
            } catch (Exception $e) {
                $this->logger->error('database connect failed', ['error' => $e->getMessage()]);
                throw new Exception($e->getMessage(), 0, $e);
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error('bootstrap failed', ['error' => $e->getMessage()]);
            throw new Exception($e->getMessage(), 0, $e);
        }

        $this->logger->info('bootstrap completed');

        return $this;
    }

    /**
     * Close application
     *
     * @return $this
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
     * @return $this
     */
    public function processRequest(): static
    {
        $router = require_once APP_ROOT . "/app/Http/routes.php";

        $this->router($router);

        return $this;
    }

    /**
     * Router
     *
     * @param RouterInterface $router Active router.
     * @return $this
     */
    protected function router(RouterInterface $router): static
    {
        $router->run();

        return $this;
    }

    /**
     * Connect to database
     *
     * @param array|null $databaseConfig Validated database config (array) or null to skip.
     * @return $this
     * @throws Exception When the database connection fails.
     */
    protected function connectToDatabase(?array $databaseConfig): static
    {
        if ($databaseConfig) {
            $this->db = DatabaseFactory::getInstance();
            $isConnected = $this->db->connect($databaseConfig);

            if (!$isConnected) {
                throw new Exception("Unable to connect to the database");
            }
        }

        return $this;
    }

    /**
     * Disconnect from a database
     *
     * @return $this
     */
    protected function disconnectFromDatabase(): static
    {
        $this->db?->disconnect();

        return $this;
    }

    /**
     * Pre processor
     *
     * @return void
     * @throws InvalidArgumentException When config lookup fails.
     */
    private function preProcessor(): void
    {
        if (Config::get("forceNonWww")) {
            PreProcessor::forceNonWww();
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
