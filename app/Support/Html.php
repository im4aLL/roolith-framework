<?php
namespace App\Support;

/**
 * HTML output escaping helpers.
 *
 * Single place for render-time escaping so views escape untrusted data
 * instead of relying on input-time sanitization. All helpers are pure
 * and never throw.
 */
final class Html
{
    /**
     * Escape a value for HTML body or attribute context.
     *
     * Stringifies scalars and null, then encodes with ENT_QUOTES in
     * UTF-8 so quotes, angle brackets, and ampersands cannot break
     * markup. Arrays and non-stringable objects are unsafe to
     * stringify (array to string yields "Array"), so they return an
     * empty string instead of leaking structure. Stringable objects
     * use their __toString() form.
     *
     * @param mixed $value Raw value to escape.
     * @return string Escaped string safe for HTML output.
     */
    public static function escape(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '';
        } elseif ($value === null) {
            $value = '';
        } elseif (is_object($value)) {
            $value = (string) $value;
        } elseif (!is_string($value) && !is_scalar($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
