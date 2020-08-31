<?php
namespace App\Core;

use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;

class System
{
    protected $db;

    public function __construct()
    {
        $this->db = null;
    }

    public function bootstrap()
    {
        $this->connectToDatabase();

        return $this;
    }

    public function complete()
    {
        $this->disconnectFromDatabase();

        return $this;
    }

    protected function connectToDatabase()
    {
        try {
            $databaseConfig = Config::get('database');

            if ($databaseConfig) {
                $this->db = DatabaseFactory::getDb();
                $this->db->connect($databaseConfig);
            }
        } catch (InvalidArgumentException $e) {
        }

        return $this;
    }

    protected function disconnectFromDatabase()
    {
        if ($this->db) {
            $this->db->disconnect();
        }

        return $this;
    }
}