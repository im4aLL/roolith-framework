<?php
namespace App\Core;


use Carbon\Carbon;

class Settings
{
    public const LANG_COOKIE_NAME = 'lang';
    public const LANG_DEFAULT = 'en';

    /**
     * Set language
     *
     * The value is sanitized to the locale allowlist first so only valid
     * locale names are ever stored in the cookie.
     *
     * @param string $lang Requested locale name.
     * @return bool True when the cookie was queued.
     */
    public static function setLang(string $lang): bool
    {
        return Storage::setCookie(self::LANG_COOKIE_NAME, Language::sanitizeLang($lang), Carbon::now()->addMonths(1));
    }

    /**
     * Get language
     *
     * Reads the lang cookie through the locale allowlist so traversal
     * payloads fall back to the default instead of reaching the loader.
     *
     * @return string Sanitized locale name or the en default.
     */
    public static function getLang(): string
    {
        $lang = Request::cookie(self::LANG_COOKIE_NAME);

        return Language::sanitizeLang($lang);
    }
}
