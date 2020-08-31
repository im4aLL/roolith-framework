<?php
namespace App\Controllers;


use App\Core\TemplateEngineFactory;
use Roolith\Template\Engine\Exceptions\Exception;

class Controller
{
    private $templateEngine;

    public function __construct()
    {
        $this->templateEngine = TemplateEngineFactory::getInstance();
    }

    /**
     * Render template engine
     *
     * @param $filename
     * @param array $data
     * @return false|string
     */
    public function view($filename, $data = [])
    {
        try {
            return $this->templateEngine->compile($filename, $data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }
}