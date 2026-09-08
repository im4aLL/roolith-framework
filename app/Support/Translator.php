<?php
namespace App\Support;

use App\Utils\Str;

/**
 * Translation helper.
 *
 * Backs the global trans() function and its __() BC alias. Resolves
 * dotted message keys for the active locale via Str::getMessage().
 * Missing keys return null so callers can fall back.
 */
final class Translator
{
    /**
     * Get a translated message.
     *
     * Resolves the dotted key for the active locale. Returns null when
     * the key or locale dictionary is missing so views can fall back
     * to a default string.
     *
     * @param string $key Message key in dot notation (for example errors.required).
     * @return mixed Message value or null when missing.
     */
    public static function trans(string $key): mixed
    {
        return Str::getMessage($key);
    }
}
