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
     * @param $lang
     * @return bool
     */
    public static function setLang($lang)
    {
        return Storage::setCookie(self::LANG_COOKIE_NAME, $lang, Carbon::now()->addMonths());
    }

    /**
     * Get language
     *
     * @return mixed|string|null
     */
    public static function getLang()
    {
        $lang = Request::cookie(self::LANG_COOKIE_NAME);

        return $lang ? $lang : self::LANG_DEFAULT;
    }
}
