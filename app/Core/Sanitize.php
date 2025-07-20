<?php
namespace App\Core;


use App\Core\Interfaces\SanitizeInterface;

class Sanitize implements SanitizeInterface
{
    /**
     * @inheritDoc
     */
    public static function param($string): string
    {
        return preg_replace("/[^a-zA-Z0-9-._]+/", "", $string);
    }

    /**
     * Filter multiple param
     *
     * @param array $params
     * @return array
     */
    public static function params(array $params): array
    {
        return array_map(function ($value) {
            return self::param($value);
        }, $params);
    }

    /**
     * @inheritDoc
     */
    public static function email($string): string
    {
        return preg_replace("/[^a-z0-9+_.@-]/i", "", $string);
    }

    /**
     * @inheritDoc
     */
    public static function any(mixed $str): string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<[/!]*?[^<>]*?>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        ];

        $str = preg_replace($search, '', $str);

        $str = strip_tags(trim($str));
        $str = htmlentities($str, ENT_QUOTES, "UTF-8");

        if (function_exists('get_magic_quotes_gpc')) {
            $str = stripslashes($str);
        }

        return $str;
    }

    /**
     * @inheritDoc
     */
    public static function string($string): string
    {
        return filter_var($string, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH);
    }

    /**
     * @inheritDoc
     */
    public static function items(array $items): array
    {
        return array_map(function ($itemValue) {
            if (is_array($itemValue)) {
                return self::items($itemValue);
            }

            return self::any($itemValue);
        }, $items);
    }
}
