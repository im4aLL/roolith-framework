<?php
namespace Tests;

use App\Core\Language;
use App\Core\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Covers locale allowlisting against LFI payloads.
 */
class LanguageTest extends TestCase
{
    /**
     * Cookie keys touched by language tests.
     *
     * @var array<int, string>
     */
    private const COOKIE_KEY = 'lang';

    /**
     * Original cookie value for restore.
     *
     * @var mixed
     */
    private mixed $cookieBackup = null;

    /**
     * Whether the cookie existed before the test.
     *
     * @var bool
     */
    private bool $cookieExists = false;

    /**
     * Snapshot cookie state and clear the locale cache.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        $this->cookieExists = array_key_exists(self::COOKIE_KEY, $_COOKIE);
        $this->cookieBackup = $this->cookieExists ? $_COOKIE[self::COOKIE_KEY] : null;

        Language::resetForTests();
    }

    /**
     * Restore cookie state and clear the locale cache.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->cookieExists) {
            $_COOKIE[self::COOKIE_KEY] = $this->cookieBackup;
        } else {
            unset($_COOKIE[self::COOKIE_KEY]);
        }

        Language::resetForTests();
    }

    /**
     * Traversal payloads must fall back to en.
     *
     * @return void
     */
    public function testTraversalPayloadFallsBackToEn(): void
    {
        $this->assertSame('en', Language::sanitizeLang('../../etc/passwd'));
        $this->assertSame('en', Language::sanitizeLang('../../x'));
        $this->assertSame('en', Language::sanitizeLang('../en'));
        $this->assertSame('en', Language::sanitizeLang('en/../es'));
        $this->assertSame('en', Language::sanitizeLang(''));
    }

    /**
     * Non-string and unknown locales must fall back to en.
     *
     * @return void
     */
    public function testInvalidLocalesFallBackToEn(): void
    {
        $this->assertSame('en', Language::sanitizeLang(null));
        $this->assertSame('en', Language::sanitizeLang(['en']));
        $this->assertSame('en', Language::sanitizeLang('EN'));
        $this->assertSame('en', Language::sanitizeLang('xx'));
        $this->assertSame('en', Language::sanitizeLang('en-US-extra'));
    }

    /**
     * Known locales from lang/ must pass through.
     *
     * @return void
     */
    public function testKnownLocalesPassThrough(): void
    {
        $this->assertSame('en', Language::sanitizeLang('en'));
        $this->assertSame('es', Language::sanitizeLang('es'));
    }

    /**
     * Loading with a traversal payload must resolve to the en catalog.
     *
     * @return void
     */
    public function testLoadWithTraversalResolvesToEnCatalog(): void
    {
        $language = new Language();
        $messages = $language->getMessages('../../etc/passwd');

        $this->assertIsArray($messages);
        $this->assertSame('Welcome', $messages['welcome'] ?? null);
    }

    /**
     * Cookie traversal payload must resolve to en via Settings.
     *
     * @return void
     */
    public function testCookieTraversalFallsBackToEn(): void
    {
        $_COOKIE[self::COOKIE_KEY] = '../../etc/passwd';

        $this->assertSame('en', Settings::getLang());
    }

    /**
     * Missing cookie must resolve to en via Settings.
     *
     * @return void
     */
    public function testMissingCookieFallsBackToEn(): void
    {
        unset($_COOKIE[self::COOKIE_KEY]);

        $this->assertSame('en', Settings::getLang());
    }
}
