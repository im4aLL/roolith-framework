<?php
namespace App\Controllers;


use App\Core\TemplateEngineFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Template\Engine\Exceptions\Exception as TemplateException;
use Roolith\Template\Engine\Exceptions\InvalidArgumentException as TemplateInvalidArgumentException;

class Controller
{
    /**
     * Template engine instance.
     *
     * @var null|\Roolith\Template\Engine\Interfaces\ViewInterface|\Roolith\Template\Engine\View
     */
    private null|\Roolith\Template\Engine\Interfaces\ViewInterface|\Roolith\Template\Engine\View $templateEngine;

    /**
     * Controller constructor.
     *
     * @throws \App\Core\Exceptions\Exception When baseUrl is not configured.
     */
    public function __construct()
    {
        $this->templateEngine = TemplateEngineFactory::getInstance();

        try {
            $this->templateEngine->setBaseUrl(Config::get('baseUrl'));
        } catch (InvalidArgumentException $e) {
            throw new \App\Core\Exceptions\Exception("`baseUrl` is not defined!", 0, $e);
        }
    }

    /**
     * Render template engine
     *
     * Fail-closed: template failures are never echoed (which would leak
     * paths with HTTP 200). The exception is rethrown with view context
     * so the front-controller ErrorHandler logs the full trace and
     * returns a generic 500 in prod (details only in dev via Whoops).
     * BC break: false is never returned; failures throw instead.
     *
     * @param string $filename View name.
     * @param array<string, mixed> $data View data.
     * @return string Rendered HTML.
     * @throws \App\Core\Exceptions\Exception When rendering fails.
     */
    public function view(string $filename, array $data = []): string
    {
        try {
            return $this->templateEngine->compile($filename, $data);
        } catch (TemplateException | TemplateInvalidArgumentException $e) {
            throw new \App\Core\Exceptions\Exception(
                "Failed rendering view '{$filename}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
