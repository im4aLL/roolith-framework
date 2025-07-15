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
     * @return null|mixed
     */
    public static function input($name, $default = null);

    /**
     * Whether has not input or not
     *
     * @param $name
     * @return bool
     */
    public static function has($name);

    /**
     * Get all input
     *
     * @return array
     */
    public static function all();

    /**
     * Get only specified array
     *
     * @param $name string|array
     * @return array
     */
    public static function only($name);

    /**
     * Get all input except provided
     *
     * @param $name string|array
     * @return array
     */
    public static function except($name);

    /**
     * Redirect to given url
     *
     * @param $url
     * @return void
     */
    public static function redirect($url);

    /**
     * Get cookie by name
     *
     * @param $name
     * @return null|string
     */
    public static function cookie($name);

    /**
     * Get file in request
     *
     * @param $name
     * @param $fileData
     * @return false|FileInterface
     */
    public static function file($name, $fileData);

    /**
     * If request has file by name
     *
     * @param $name
     * @return bool
     */
    public static function hasFile($name);

    /**
     * If request type is ajax
     *
     * @return bool
     */
    public static function ajax();

    /**
     * Get request url
     *
     * @return string
     */
    public static function url();

    /**
     * Get full url with query param
     *
     * @return string
     */
    public static function fullUrl();

    /**
     * Get request method name
     *
     * @return string
     */
    public static function method();

    /**
     * Check whether request method name match
     *
     * @param $methodName string
     * @return bool
     */
    public static function isMethod($methodName);
}
