<?php
namespace App\Core\Interfaces;


interface SanitizeInterface
{
    /**
     * Sanitize url string.
     *
     * @param string $string Raw param value.
     * @return string Sanitized param.
     */
    public static function param(string $string): string;

    /**
     * Sanitize email string.
     *
     * @param string $string Raw email value.
     * @return string Sanitized email.
     */
    public static function email(string $string): string;

    /**
     * Sanitize any string.
     *
     * @param mixed $str Raw value (stringified when scalar).
     * @return string Sanitized string.
     */
    public static function any(mixed $str): string;

    /**
     * Sanitize string.
     *
     * @param string $string Raw string value.
     * @return string Sanitized string.
     */
    public static function string(string $string): string;

    /**
     * Sanitize multiple items at once.
     *
     * @param array<int|string, mixed> $items Raw items.
     * @return array<int|string, mixed> Sanitized items.
     */
    public static function items(array $items): array;
}
