<?php
namespace Tests;

use App\Core\DatabaseFactory;
use App\Core\Lang;
use App\Core\Language;
use App\Core\Request;
use App\Core\RouterFactory;
use App\Core\Sanitize;
use App\Core\Storage;
use App\Core\TemplateEngineFactory;
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

/**
 * Proves static facade reset seams isolate tests.
 *
 * Each facade exposes resetForTests() (Config uses its existing reset())
 * so cached singletons do not leak across tests. Facades stay thin proxies:
 * reset only drops the cached instance, it does not inject dependencies.
 */
class FacadeResetTest extends TestCase
{
    /**
     * Define APP_ROOT for factories needing view paths.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', APP_ROOT . '/views');
        }

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
        }
    }

    /**
     * Reset all facades after each test for isolation.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Request::resetForTests();
        RouterFactory::resetForTests();
        DatabaseFactory::resetForTests();
        TemplateEngineFactory::resetForTests();
        Lang::resetForTests();
        Language::resetForTests();
        Storage::resetForTests();
        Sanitize::resetForTests();
        Config::reset(false);
    }

    /**
     * RouterFactory must return a fresh instance after reset.
     *
     * @return void
     */
    public function testRouterFactoryResets(): void
    {
        $first = RouterFactory::getInstance();
        RouterFactory::resetForTests();
        $second = RouterFactory::getInstance();

        $this->assertNotSame($first, $second);
    }

    /**
     * DatabaseFactory must return a fresh instance after reset.
     *
     * @return void
     */
    public function testDatabaseFactoryResets(): void
    {
        $first = DatabaseFactory::getInstance();
        DatabaseFactory::resetForTests();
        $second = DatabaseFactory::getInstance();

        $this->assertNotSame($first, $second);
    }

    /**
     * TemplateEngineFactory must return a fresh instance after reset.
     *
     * @return void
     */
    public function testTemplateEngineFactoryResets(): void
    {
        $first = TemplateEngineFactory::getInstance();
        TemplateEngineFactory::resetForTests();
        $second = TemplateEngineFactory::getInstance();

        $this->assertNotSame($first, $second);
    }

    /**
     * Lang must return a fresh instance after reset.
     *
     * @return void
     */
    public function testLangResets(): void
    {
        $first = Lang::getInstance();
        Lang::resetForTests();
        $second = Lang::getInstance();

        $this->assertNotSame($first, $second);
    }

    /**
     * Request stream cache reset must be callable and leave has() runnable.
     *
     * @return void
     */
    public function testRequestResetClearsStreamCache(): void
    {
        Request::resetForTests();
        Request::resetStreamInputsForTests();

        $this->assertFalse(Request::has('facade-reset-probe-' . uniqid()));
    }

    /**
     * Storage and Sanitize resets must be callable without error.
     *
     * @return void
     */
    public function testStorageAndSanitizeResetsAreCallable(): void
    {
        Storage::resetForTests();
        Sanitize::resetForTests();

        $this->assertSame('abc', Sanitize::param('abc'));
    }

    /**
     * Vendor Config already exposes reset() for test isolation.
     *
     * @return void
     */
    public function testVendorConfigResetExists(): void
    {
        $this->assertTrue(method_exists(Config::class, 'reset'));

        Config::reset(false);

        $this->assertTrue(true);
    }
}
