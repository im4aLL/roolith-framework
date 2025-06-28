<?php
namespace App\Core;

use Roolith\Store\Database;
use Roolith\Store\Interfaces\DatabaseInterface;

class DatabaseFactory
{
    private static DatabaseInterface | null $db = null;

    private function __construct() {}

    /**
     * @return DatabaseInterface
     */
    public static function getInstance(): DatabaseInterface
    {
        if (self::$db === null) {
            self::$db = new Database();
        }

        return self::$db;
    }
}
