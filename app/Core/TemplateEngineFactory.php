<?php
namespace App\Core;


use Roolith\Template\Engine\Interfaces\ViewInterface;
use Roolith\Template\Engine\View;

class TemplateEngineFactory
{
    /**
     * Cached template engine instance.
     *
     * @var ViewInterface|null
     */
    private static ?ViewInterface $view = null;

    /**
     * Private constructor to enforce factory use.
     */
    private function __construct() {}

    /**
     * Get the shared template engine instance.
     *
     * @return ViewInterface Shared view engine.
     */
    public static function getInstance(): ViewInterface
    {
        if (self::$view === null) {
            self::$view = new View(APP_VIEW_ROOT);
        }

        return self::$view;
    }

    /**
     * Clear the cached view engine instance (test seam).
     *
     * Keeps the facade a thin proxy: no dependencies are injected here,
     * this only drops the singleton so tests can boot an isolated engine
     * per test without order dependence.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$view = null;
    }
}