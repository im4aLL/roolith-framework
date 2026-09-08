<?php
namespace App\Core;


use App\Core\Interfaces\SanitizeInterface;

/**
 * String sanitizers for request input.
 *
 * Pure helpers: param() keeps URL-safe chars, email() keeps email chars,
 * any() strips tags/scripts and encodes entities, items() maps any() over
 * arrays. All string inputs are cast upstream; non-string scalars passed
 * to any() are stringified.
 */
class Sanitize implements SanitizeInterface
{
    /**
     * Reset facade state for tests (test seam).
     *
     * Sanitize is stateless and holds no cache; this is a no-op so test
     * suites can reset all facades uniformly without special-casing.
     * Keeps the facade a thin proxy over pure string helpers.
     *
     * @return void
     */
    public static function resetForTests(): void
    {
    }

    /**
     * Sanitize a URL-safe param string.
     *
     * Keeps letters, digits, dash, dot, and underscore; all else stripped.
     *
     * @param string $string Raw param value.
     * @return string Sanitized param.
     */
    public static function param(string $string): string
    {
        $cleaned = preg_replace("/[^a-zA-Z0-9-._]+/", "", $string);

        return is_string($cleaned) ? $cleaned : "";
    }

    /**
     * Filter multiple params.
     *
     * @param array<int|string, mixed> $params Raw param values keyed by name.
     * @return array<int|string, string> Sanitized params.
     */
    public static function params(array $params): array
    {
        return array_map(function (mixed $value): string {
            return self::param((string) $value);
        }, $params);
    }

    /**
     * Sanitize an email string.
     *
     * @param string $string Raw email value.
     * @return string Sanitized email.
     */
    public static function email(string $string): string
    {
        $cleaned = preg_replace("/[^a-z0-9+_.@-]/i", "", $string);

        return is_string($cleaned) ? $cleaned : "";
    }

    /**
     * Sanitize any string value.
     *
     * @param mixed $str Raw value (stringified when scalar).
     * @return string Sanitized string.
     */
    public static function any(mixed $str): string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<[/!]*?[^<>]*?>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        ];

        $text = (string) $str;
        $cleaned = preg_replace($search, '', $text);
        $text = is_string($cleaned) ? $cleaned : $text;

        $text = strip_tags(trim($text));
        $text = htmlentities($text, ENT_QUOTES, "UTF-8");

        if (function_exists('get_magic_quotes_gpc')) {
            $text = stripslashes($text);
        }

        return $text;
    }

    /**
     * Sanitize a plain string.
     *
     * @param string $string Raw string value.
     * @return string Sanitized string.
     */
    public static function string(string $string): string
    {
        $filtered = filter_var($string, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH);

        return is_string($filtered) ? $filtered : "";
    }

    /**
     * Sanitize multiple items at once.
     *
     * @param array<int|string, mixed> $items Raw items.
     * @return array<int|string, mixed> Sanitized items (arrays recurse, scalars stringified via any()).
     */
    public static function items(array $items): array
    {
        return array_map(function (mixed $itemValue): mixed {
            if (is_array($itemValue)) {
                return self::items($itemValue);
            }

            return self::any($itemValue);
        }, $items);
    }
}
