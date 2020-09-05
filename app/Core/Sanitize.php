<?php
namespace App\Core;


use App\Core\Interfaces\SanitizeInterface;

class Sanitize implements SanitizeInterface
{
    /**
     * @inheritDoc
     */
    public static function url($string)
    {
        return preg_replace("/[^a-zA-Z0-9-._]+/", "", $string);
    }

    /**
     * @inheritDoc
     */
    public static function email($string)
    {
        return preg_replace("/[^a-z0-9+_.@-]/i", "", $string);
    }

    /**
     * @inheritDoc
     */
    public static function any($string)
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<[\/\!]*?[^<>]*?>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        ];

        $str = preg_replace($search, '', $string);

        $str = strip_tags(trim($str));
        $str = htmlentities($str, ENT_QUOTES, "UTF-8");

        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        return $str;
    }

    /**
     * @inheritDoc
     */
    public static function string($string)
    {
        return filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    }
}