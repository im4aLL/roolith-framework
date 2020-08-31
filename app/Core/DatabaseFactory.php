<?php
namespace App\Core;

use Roolith\Database;

class DatabaseFactory
{
    private static $instance = null;

    private function __construct() {}

    /**
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }
}