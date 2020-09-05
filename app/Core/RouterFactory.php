<?php
namespace App\Core;


use Roolith\Route\Router;

class RouterFactory
{
    private static $router = null;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$router === null) {
            self::$router = new Router();
        }

        return self::$router;
    }
}