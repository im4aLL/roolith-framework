<?php
namespace App\Core;

use Roolith\Config;
use Roolith\Exception\InvalidArgumentException;

class System
{
    protected $db;

    public function __construct()
    {
        $this->db = null;
    }

    public function connectToDatabase()
    {
        try {
            $databaseConfig = Config::get('database');

            if ($databaseConfig) {
                $this->db = DatabaseFactory::getInstance();
                $this->db->connect($databaseConfig);
            }
        } catch (InvalidArgumentException $e) {
        }
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function disconnectFromDatabase()
    {
        if ($this->db) {
            $this->db->disconnect();
        }
    }
}