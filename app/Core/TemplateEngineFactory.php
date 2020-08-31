<?php
namespace App\Core;


use Roolith\Template\Engine\Interfaces\ViewInterface;
use Roolith\Template\Engine\View;

class TemplateEngineFactory
{
    private static $view = null;

    private function __construct() {}

    /**
     * @return ViewInterface
     */
    public static function getInstance()
    {
        if (self::$view === null) {
            self::$view = new View(APP_VIEW_ROOT);
        }

        return self::$view;
    }
}