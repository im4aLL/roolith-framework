<?php
namespace App\Core\Interfaces;


interface RequestInterface
{
    /**
     * Get request input
     * If there is no input but default value pass then return default
     *
     * @param $name
     * @param $default
     * @return false|mixed
     */
    public function input($name, $default);

    /**
     * Whether has not input or not
     *
     * @param $name
     * @return bool
     */
    public function has($name);

    /**
     * Get all input
     *
     * @return object
     */
    public function all();

    /**
     * Get only specified object
     *
     * @param $name string|array
     * @return object|false
     */
    public function only($name);

    /**
     * Get all input except provided
     *
     * @param $name string|array
     * @return object|false
     */
    public function except($name);

    /**
     * Remove all inputs
     *
     * @return bool
     */
    public function flush();

    /**
     * Remove only provided input names
     *
     * @param $name string|array
     * @return bool
     */
    public function flashOnly($name);

    /**
     * Remove all input except provided ones
     *
     * @param $name string|array
     * @return bool
     */
    public function flashExcept($name);

    /**
     * Redirect to given url
     *
     * @param $url
     * @return bool
     */
    public function redirect($url);

    /**
     * Get old value for a input
     *
     * @param $name
     * @return mixed
     */
    public function old($name);

    /**
     * Get cookie by name
     *
     * @param $name
     * @return mixed
     */
    public function cookie($name);

    /**
     * Get file in request
     *
     * @param $name
     * @return FileInterface
     */
    public function file($name);

    /**
     * If request has file by name
     *
     * @param $name
     * @return bool
     */
    public function hasFile($name);

    /**
     * If request type is ajax
     *
     * @return bool
     */
    public function ajax();

    /**
     * Get request url
     *
     * @return string
     */
    public function url();

    /**
     * Get request method name
     *
     * @return string
     */
    public function method();

    /**
     * Check whether request method name match
     *
     * @param $methodName string
     * @return bool
     */
    public function isMethod($methodName);
}