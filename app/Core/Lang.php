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

    /**
     * Clear the cached Language instance (test seam).
     *
     * Keeps the facade a thin proxy: no dependencies are injected here,
     * this only drops the singleton so tests can load an isolated catalog
     * per test without order dependence. Also clears the Language locale
     * allowlist cache so lang/ scans do not leak across tests.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$instance = null;
        Language::resetForTests();
    }
}
