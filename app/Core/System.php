<?php
namespace App\Core;

use App\Core\Exceptions\Exception;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Route\Interfaces\RouterInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class System
{
    /**
     * @var ?DatabaseInterface
     */
    protected ?DatabaseInterface $db;

    public function __construct()
    {
        require_once APP_ROOT . '/constant.php';
        require_once APP_ROOT . '/app/Utils/functions.php';

        $this->db = null;
        $this->registerCustomError();
    }

    /**
     * Bootstrap application
     *
     * @return $this
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function bootstrap(): static
    {
        $this->preProcessor();

        try {
            $dbConfig = Config::get('database');

            try {
                $this->connectToDatabase($dbConfig);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage());
        }

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
        $router = require_once APP_ROOT . '/app/Http/routes.php';

        $this->router($router);

        return $this;
    }

    /**
     * Router
     *
     * @param RouterInterface $router
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
     * @param $databaseConfig
     * @return $this
     * @throws Exception
     */
    protected function connectToDatabase($databaseConfig): static
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
     * @throws InvalidArgumentException
     */
    private function preProcessor(): void
    {
        if (Config::get('forceNonWww')) {
            PreProcessor::forceNonWww();
        }

        if (Config::get('forceWww')) {
            PreProcessor::forceWww();
        }
    }

    /**
     * Register custom error
     *
     * @return void
     */
    private function registerCustomError(): void
    {
        if (isProductionEnvironment()) {
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(E_ALL);

            return;
        }

        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    }
}
