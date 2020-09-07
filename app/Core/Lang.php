<?php
namespace App\Core;


class Lang
{
    private static $instance = null;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Language();
        }

        return self::$instance;
    }
}
