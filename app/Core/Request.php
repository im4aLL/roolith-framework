<?php
namespace App\Core;


use App\Core\Interfaces\FileInterface;
use App\Core\Interfaces\RequestInterface;

class Request implements RequestInterface
{

    /**
     * @inheritDoc
     */
    public function input($name, $default)
    {
        // TODO: Implement input() method.
    }

    /**
     * @inheritDoc
     */
    public function has($name)
    {
        // TODO: Implement has() method.
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        // TODO: Implement all() method.
    }

    /**
     * @inheritDoc
     */
    public function only($name)
    {
        // TODO: Implement only() method.
    }

    /**
     * @inheritDoc
     */
    public function except($name)
    {
        // TODO: Implement except() method.
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * @inheritDoc
     */
    public function flashOnly($name)
    {
        // TODO: Implement flashOnly() method.
    }

    /**
     * @inheritDoc
     */
    public function flashExcept($name)
    {
        // TODO: Implement flashExcept() method.
    }

    /**
     * @inheritDoc
     */
    public function redirect($url)
    {
        // TODO: Implement redirect() method.
    }

    /**
     * @inheritDoc
     */
    public function old($name)
    {
        // TODO: Implement old() method.
    }

    /**
     * @inheritDoc
     */
    public function cookie($name)
    {
        // TODO: Implement cookie() method.
    }

    /**
     * @inheritDoc
     */
    public function file($name)
    {
        // TODO: Implement file() method.
    }

    /**
     * @inheritDoc
     */
    public function hasFile($name)
    {
        // TODO: Implement hasFile() method.
    }

    /**
     * @inheritDoc
     */
    public function ajax()
    {
        // TODO: Implement ajax() method.
    }

    /**
     * @inheritDoc
     */
    public function url()
    {
        // TODO: Implement url() method.
    }

    /**
     * @inheritDoc
     */
    public function method()
    {
        // TODO: Implement method() method.
    }

    /**
     * @inheritDoc
     */
    public function isMethod($methodName)
    {
        // TODO: Implement isMethod() method.
    }
}