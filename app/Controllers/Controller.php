<?php
namespace App\Controllers;


use App\Core\TemplateEngineFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Template\Engine\Exceptions\Exception;

class Controller
{
    private null|\Roolith\Template\Engine\Interfaces\ViewInterface|\Roolith\Template\Engine\View $templateEngine;

    /**
     * Controller constructor.
     *
     * @throws \App\Core\Exceptions\Exception
     */
    public function __construct()
    {
        $this->templateEngine = TemplateEngineFactory::getInstance();

        try {
            $this->templateEngine->setBaseUrl(Config::get('baseUrl'));
        } catch (InvalidArgumentException $e) {
            throw new \App\Core\Exceptions\Exception("`baseUrl` is not defined!");
        }
    }

    /**
     * Render template engine
     *
     * @param $filename
     * @param array $data
     * @return string|bool
     */
    public function view($filename, array $data = []): string|bool
    {
        try {
            return $this->templateEngine->compile($filename, $data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }
}
