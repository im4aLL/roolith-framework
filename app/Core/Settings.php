<?php
namespace App\Core;


use Carbon\Carbon;

/**
 * App settings: locale and timezone resolution plus language cookie.
 *
 * Timezone resolves Config `timezone`, then Env `APP_TIMEZONE`, then UTC;
 * locale resolves Config `locale`, then Env `APP_LOCALE`, then en. Use
 * applyDefaultTimezone() to install the timezone in the PHP process.
 */
class Settings
{
    /**
     * Cookie name holding the locale.
     *
     * @var string
     */
    public const LANG_COOKIE_NAME = 'lang';

    /**
     * Default locale (BC alias for defaultLocale() fallback).
     *
     * Kept as a constant for backwards compatibility; new code should call
     * defaultLocale() which reads config/env with this as the final default.
     *
     * @var string
     */
    public const LANG_DEFAULT = 'en';

    /**
     * Default timezone when none is configured (sane UTC default).
     *
     * @var string
     */
    public const TIMEZONE_DEFAULT = 'UTC';

    /**
     * Resolve the default locale from config/env with a sane fallback.
     *
     * Reads Config `locale` first, then Env `APP_LOCALE`, then `en`. Values
     * must match the locale shape (two lowercase letters with optional
     * region); invalid values fall back so bootstrap never fatals.
     * Documented here so timezone/locale configurability is discoverable.
     *
     * @return string Configured locale or en.
     */
    public static function defaultLocale(): string
    {
        try {
            $configured = \Roolith\Configuration\Config::get('locale');
        } catch (\Throwable) {
            $configured = null;
        }

        if (is_string($configured) && preg_match(Language::LOCALE_PATTERN, trim($configured)) === 1) {
            return trim($configured);
        }

        try {
            $env = Env::get('APP_LOCALE');
        } catch (\Throwable) {
            $env = null;
        }

        if (is_string($env) && preg_match(Language::LOCALE_PATTERN, trim($env)) === 1) {
            return trim($env);
        }

        return self::LANG_DEFAULT;
    }

    /**
     * Resolve the app timezone from config/env with a sane UTC default.
     *
     * Reads Config `timezone` first, then Env `APP_TIMEZONE`, then `UTC`.
 * Unknown identifiers fall back to UTC so date_default_timezone_set()
 * never fails. Documented here alongside defaultLocale().
 *
     * @return string Valid PHP timezone identifier.
     */
    public static function defaultTimezone(): string
    {
        try {
            $configured = \Roolith\Configuration\Config::get('timezone');
        } catch (\Throwable) {
            $configured = null;
        }

        if (is_string($configured) && trim($configured) !== '' && in_array(trim($configured), timezone_identifiers_list(), true)) {
            return trim($configured);
        }

        try {
            $env = Env::get('APP_TIMEZONE');
        } catch (\Throwable) {
            $env = null;
        }

        if (is_string($env) && trim($env) !== '' && in_array(trim($env), timezone_identifiers_list(), true)) {
            return trim($env);
        }

        return self::TIMEZONE_DEFAULT;
    }

    /**
     * Apply the resolved timezone to the PHP process.
     *
     * Calls defaultTimezone() (Config `timezone`, then Env `APP_TIMEZONE`,
     * then UTC) and installs it via date_default_timezone_set(). Never
     * throws: an invalid identifier falls back to UTC inside
     * defaultTimezone(), and a set failure keeps the current timezone.
     * Returns the applied identifier so front-controller and bootstrap
     * callers can assert the effective value in tests.
     *
     * @return string Applied timezone identifier.
     */
    public static function applyDefaultTimezone(): string
    {
        $timezone = self::defaultTimezone();

        try {
            date_default_timezone_set($timezone);
        } catch (\Throwable) {
            // Keep the current timezone when the set fails.
        }

        return $timezone;
    }

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
     * Get language.
     *
     * Reads the lang cookie through the locale allowlist so traversal
     * payloads fall back instead of reaching the loader. When no cookie is
     * present or it is invalid, falls back to defaultLocale() (config
     * `locale` or Env `APP_LOCALE`, default en).
     *
     * @return string Sanitized locale name or the configured default.
     */
    public static function getLang(): string
    {
        $lang = Request::cookie(self::LANG_COOKIE_NAME);

        if (!is_string($lang) || trim($lang) === '') {
            return self::defaultLocale();
        }

        $sanitized = Language::sanitizeLang($lang);

        if ($sanitized === Language::FALLBACK_LANG && trim($lang) !== Language::FALLBACK_LANG) {
            $default = self::defaultLocale();

            if ($default !== Language::FALLBACK_LANG) {
                return $default;
            }
        }

        return $sanitized;
    }
}
