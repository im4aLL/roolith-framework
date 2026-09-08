<?php
namespace App\Core;


use App\Utils\FS;

class Language
{
    /**
     * Allowed locale shape: two lowercase letters with an optional region.
     *
     * @var string
     */
    public const LOCALE_PATTERN = '/^[a-z]{2}(-[A-Z]{2})?$/';

    /**
     * Fallback locale when the requested one is invalid or missing.
     *
     * @var string
     */
    public const FALLBACK_LANG = 'en';

    /**
     * Cached allowlist of locale directory names under lang/.
     *
     * @var array<int, string>|null
     */
    private static ?array $allowedLocales = null;

    /**
     * Loaded message catalogs keyed by sanitized locale.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $messages;

    /**
     * Create an empty catalog holder.
     */
    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * Sanitize a locale against the allowlist and shape, falling back to en.
     *
     * Fail-closed: non-strings, bad shapes, and names without a matching
     * lang/ directory all resolve to the fallback so no path traversal
     * payload can reach the include below.
     *
     * @param mixed $lang Raw locale value (for example the lang cookie).
     * @return string Sanitized locale name or the en fallback.
     */
    public static function sanitizeLang(mixed $lang): string
    {
        if (!is_string($lang)) {
            return self::FALLBACK_LANG;
        }

        $candidate = trim($lang);

        if (preg_match(self::LOCALE_PATTERN, $candidate) !== 1) {
            return self::FALLBACK_LANG;
        }

        if (!in_array($candidate, self::allowedLocales(), true)) {
            return self::FALLBACK_LANG;
        }

        return $candidate;
    }

    /**
     * List locale names that have a directory under lang/.
     *
     * Scanned once per process and cached; unreadable or missing lang/
     * falls back to the en default so validation stays fail-closed.
     *
     * @return array<int, string> Allowed locale directory names.
     */
    public static function allowedLocales(): array
    {
        if (self::$allowedLocales !== null) {
            return self::$allowedLocales;
        }

        $basePath = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);
        $langDir = rtrim($basePath, "/\\") . '/lang';

        if (!is_dir($langDir)) {
            self::$allowedLocales = [self::FALLBACK_LANG];

            return self::$allowedLocales;
        }

        $entries = scandir($langDir);

        if ($entries === false) {
            self::$allowedLocales = [self::FALLBACK_LANG];

            return self::$allowedLocales;
        }

        $locales = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (preg_match(self::LOCALE_PATTERN, $entry) !== 1) {
                continue;
            }

            if (!is_dir($langDir . '/' . $entry)) {
                continue;
            }

            $locales[] = $entry;
        }

        if (!in_array(self::FALLBACK_LANG, $locales, true)) {
            $locales[] = self::FALLBACK_LANG;
        }

        sort($locales);
        self::$allowedLocales = $locales;

        return self::$allowedLocales;
    }

    /**
     * Clear the cached locale allowlist (test seam).
     *
     * @return void
     */
    public static function resetForTests(): void
    {
        self::$allowedLocales = null;
    }

    /**
     * Load message by lang
     *
     * The locale is sanitized first so cookie input can never escape
     * lang/ via ../ sequences; unknown locales fall back to en.
     *
     * @param string $lang Requested locale name.
     * @return $this
     */
    public function loadMessageByLang(string $lang): static
    {
        $sanitized = self::sanitizeLang($lang);
        $basePath = defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2);
        $filePath = rtrim($basePath, "/\\") . '/lang/' . $sanitized . '/message.php';

        if (FS::exists($filePath)) {
            $catalog = include $filePath;

            if (is_array($catalog)) {
                $this->messages[$sanitized] = $catalog;
            }
        }

        return $this;
    }

    /**
     * Get a message array
     *
     * Always returns an array: missing catalogs resolve to the en
     * fallback, and a missing fallback yields an empty array instead of
     * an undefined-index warning.
     *
     * @param string|null $lang Requested locale or null for the default.
     * @return array<string, mixed>
     */
    public function getMessages(?string $lang = null): array
    {
        $defaultLang = Settings::LANG_DEFAULT;
        $selectedLang = self::sanitizeLang($lang ?? $defaultLang);

        if (!isset($this->messages[$selectedLang])) {
            $this->loadMessageByLang($selectedLang);
        }

        if (isset($this->messages[$selectedLang])) {
            return $this->messages[$selectedLang];
        }

        $fallback = self::FALLBACK_LANG;

        if ($selectedLang !== $fallback && !isset($this->messages[$fallback])) {
            $this->loadMessageByLang($fallback);
        }

        return $this->messages[$fallback] ?? [];
    }
}
