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
     * @var DatabaseInterface
     */
    protected $db;

    public function __construct()
    {
        require_once APP_ROOT . '/constant.php';
        require_once APP_ROOT . '/app/Utils/functions.php';

        $this->db = null;
    }

    /**
     * Bootstrap application
     *
     * @return $this
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function bootstrap()
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
    public function complete()
    {
        $this->disconnectFromDatabase();

        return $this;
    }

    /**
     * Process route
     *
     * @return $this
     */
    public function processRequest()
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
    protected function router(RouterInterface $router)
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
    protected function connectToDatabase($databaseConfig)
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
     * Disconnect from database
     *
     * @return $this
     */
    protected function disconnectFromDatabase()
    {
        if ($this->db) {
            $this->db->disconnect();
        }

        return $this;
    }

    /**
     * Pre processor
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    private function preProcessor()
    {
        if (Config::get('forceNonWww')) {
            PreProcessor::forceNonWww();
        }

        if (Config::get('forceWww')) {
            PreProcessor::forceWww();
        }

        return $this;
    }
}
