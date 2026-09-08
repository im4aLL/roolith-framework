<?php
namespace App\Core;

use Roolith\Store\Database;
use Roolith\Store\Interfaces\DatabaseInterface;

/**
 * Shared database factory with a resettable singleton.
 *
 * Debug mode defaults to APP_ENV (development on, otherwise off) and is
 * applied once at creation so a per-request
 * `DatabaseFactory::getInstance()->debugMode(true)` override survives
 * later getInstance() calls in the same request.
 */
class DatabaseFactory
{
    /**
     * Cached database instance.
     *
     * @var DatabaseInterface|null
     */
    private static DatabaseInterface|null $db = null;

    /**
     * Private constructor to enforce factory use.
     */
    private function __construct() {}

    /**
     * Get the shared database instance.
     *
     * Debug mode follows APP_ENV: development enables the vendor query
     * debug log, every other env disables it so SQL never leaks to output
     * in prod. Failures return false or throw from the driver; callers
     * should log via their PSR-3 logger (see System::connectToDatabase).
     * The env default is applied only when the instance is created.
     * Per-request override: call
     * DatabaseFactory::getInstance()->debugMode(true) to enable the query
     * log for one request (for example in a dev-only debug route); later
     * getInstance() calls return the same instance without reapplying the
     * env default.
     *
     * @return DatabaseInterface Shared database instance.
     */
    public static function getInstance(): DatabaseInterface
    {
        if (self::$db === null) {
            self::$db = new Database();
            self::$db->debugMode(self::isDebugEnabled());
        }

        return self::$db;
    }

    /**
     * Check whether query debug should be on for the current environment.
     *
     * True only when APP_ENV is exactly `development`; all other envs
     * (including staging, production, unset) return false (fail-closed).
     *
     * @return bool True in development, false otherwise.
     */
    public static function isDebugEnabled(): bool
    {
        return Env::isDevelopment();
    }

    /**
     * Clear the cached database instance.
     *
     * Production reset: drops the singleton so the next getInstance()
     * creates a fresh Database with the current env default.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$db = null;
    }

    /**
     * Clear the cached database instance (test seam).
     *
     * Keeps the facade a thin proxy: no dependencies are injected here,
     * this only drops the singleton so tests can boot an isolated database
     * double per test without order dependence. Alias of reset().
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::reset();
    }
}
