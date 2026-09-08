<?php
namespace App\Core;


use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Router;

/**
 * Shared router factory with a resettable singleton.
 *
 * The singleton is intentional: routes.php expects one shared Router per
 * request via getInstance(). System::processRequest() resets before each
 * load so re-entry (tests, long-lived workers) never double-registers
 * routes on a stale instance. The router is built with RouterResponse so
 * controllers may return App\Core\Response values (JSON, redirect) and
 * have their status plus headers emitted correctly.
 */
class RouterFactory
{
    /**
     * Cached router instance.
     *
     * @var Router|null
     */
    private static ?Router $router = null;

    /**
     * Private constructor to enforce factory use.
     */
    private function __construct() {}

    /**
     * Get the shared router instance.
     *
     * Built with RouterResponse (unwraps App\Core\Response controller
     * returns) and a fresh vendor Request so re-entry stays isolated.
     *
     * @return Router Shared router.
     */    public static function getInstance(): Router
    {
        if (self::$router === null) {
            self::$router = new Router([], new RouterResponse(), new RouterRequest());
        }

        return self::$router;
    }

    /**
     * Clear the cached router instance.
     *
     * Production reset: drops the singleton so the next getInstance()
     * creates a fresh Router with an empty route table. System calls this
     * before loading routes.php to keep re-entry idempotent.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$router = null;
    }

    /**
     * Clear the cached router instance (test seam).
     *
     * Keeps the facade a thin proxy: no dependencies are injected here,
     * this only drops the singleton so tests can boot an isolated router
     * per test without order dependence. Alias of reset() for suites that
     * reset all facades uniformly.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::reset();
    }
}