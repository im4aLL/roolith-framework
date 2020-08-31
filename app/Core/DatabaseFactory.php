<?php
namespace App\Core;

use Roolith\Store\Database;
use Roolith\Store\Interfaces\DatabaseInterface;

class DatabaseFactory
{
    private static $db = null;

    private function __construct() {}

    /**
     * @return DatabaseInterface
     */
    public static function getInstance()
    {
        if (self::$db === null) {
            self::$db = new Database();
        }

        return self::$db;
    }
}