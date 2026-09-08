<?php
namespace App\Core;


class Lang
{
    /**
     * Cached Language catalog holder.
     *
     * @var Language|null
     */
    private static ?Language $instance = null;

    /**
     * Private constructor to enforce singleton use.
     */
    private function __construct() {}

    /**
     * Get the shared Language instance.
     *
     * @return Language Shared catalog holder.
     */
    public static function getInstance(): Language
    {
        if (self::$instance === null) {
            self::$instance = new Language();
        }

        return self::$instance;
    }
}
