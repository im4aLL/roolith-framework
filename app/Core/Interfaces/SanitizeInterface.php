<?php
namespace App\Core\Interfaces;


interface SanitizeInterface
{
    /**
     * Sanitize url string
     *
     * @param $string
     * @return string
     */
    public static function param($string);

    /**
     * Sanitize email string
     *
     * @param $string
     * @return string
     */
    public static function email($string);

    /**
     * Sanitize any string
     *
     * @param $str
     * @return string
     */
    public static function any($str);

    /**
     * Sanitize string
     *
     * @param $string
     * @return string
     */
    public static function string($string);

    /**
     * Sanitize multiple items at once
     *
     * @param $items array
     * @return iterable
     */
    public static function items($items);
}