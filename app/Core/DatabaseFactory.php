<?php
namespace App\Core;

use Roolith\Store\Database;

class DatabaseFactory
{
    private static $db = null;

    private function __construct() {}

    /**
     * @return Database
     */
    public static function getDb()
    {
        if (self::$db === null) {
            self::$db = new Database();
        }

        return self::$db;
    }
}