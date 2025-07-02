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
    public static function param($string): string;

    /**
     * Sanitize email string
     *
     * @param $string
     * @return string
     */
    public static function email($string): string;

    /**
     * Sanitize any string
     *
     * @param $str
     * @return string
     */
    public static function any($str): string;

    /**
     * Sanitize string
     *
     * @param $string
     * @return string
     */
    public static function string($string): string;

    /**
     * Sanitize multiple items at once
     *
     * @param $items array
     * @return array
     */
    public static function items(array $items): array;
}
