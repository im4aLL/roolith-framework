<?php
namespace App\Core;


use App\Core\Interfaces\SanitizeInterface;

/**
 * Narrow input sanitizers for slug and email cases.
 *
 * Render-time escaping is the default: keep Request::input() raw,
 * validate by type, and escape in views with escape() or
 * $this->escape(). Use Sanitize only for narrow storage or lookup
 * cases: param() for URL slugs, email() for email lookups.
 *
 * any(), string(), and items() remain for BC but are legacy: they
 * strip tags and encode entities, destroying legitimate data like
 * O'Reilly and risking double escaping when combined with view
 * escaping. Do not call them on general input.
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
     * Sanitize any string value (legacy).
     *
     * Legacy for narrow cases only: strips tags/scripts and encodes
     * entities, destroying legitimate data like O'Reilly. Prefer raw
     * Request::input() plus view escaping with escape() instead.
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
     * Sanitize a plain string (legacy).
     *
     * Legacy narrow helper. Prefer view escaping with escape().
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
     * Sanitize multiple items at once (legacy).
     *
     * Legacy for narrow cases only. Prefer raw input plus view escaping.
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
