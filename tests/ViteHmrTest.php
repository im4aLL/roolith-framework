<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers the Vite HMR edge case (033-B19).
 *
 * Proves the dev HMR client is emitted once whether viteJs or viteCss runs
 * first, and that dev plus prod URLs are escaped.
 */
class ViteHmrTest extends TestCase
{
    /**
     * Original Config singleton for restore.
     *
     * @var mixed
     */
    private mixed $originalInstance = null;

    /**
     * Original Config data for restore.
     *
     * @var array<string, mixed>
     */
    private array $originalConfigArray = [];

    /**
     * Env backup keyed by name.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Env existence flags keyed by name.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Snapshot state before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        require_once APP_ROOT . '/app/Utils/functions.php';

        $ref = new ReflectionClass(Config::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $this->originalInstance = $instanceProp->getValue();
        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $this->originalConfigArray = $configProp->getValue();

        foreach (['APP_ENV', 'VITE_DEV_SERVER'] as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
        }

        setViteManifestForTests(null);
        resetViteClientTagForTests();
    }

    /**
     * Restore state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $ref = new ReflectionClass(Config::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $this->originalInstance);
        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, $this->originalConfigArray);

        foreach (['APP_ENV', 'VITE_DEV_SERVER'] as $key) {
            if ($this->envExists[$key]) {
                $_ENV[$key] = $this->envBackup[$key];
            } else {
                unset($_ENV[$key]);
            }

            unset($_SERVER[$key]);
            putenv($key);
        }

        setViteManifestForTests(null);
        resetViteClientTagForTests();
    }

    /**
     * Seed isolated Config state.
     *
     * @param array<string, mixed> $defaultConfig Config values.
     * @return void
     */
    private function seedConfig(array $defaultConfig): void
    {
        $ref = new ReflectionClass(Config::class);
        $dummy = $ref->newInstanceWithoutConstructor();
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);
        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => $defaultConfig]);
    }

    /**
     * Set an env key in all sources.
     *
     * @param string $key Env key to set.
     * @param string $value Value to assign.
     * @return void
     */
    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    /**
     * JS-only pages must still emit the HMR client once.
     *
     * @return void
     */
    public function testJsFirstStillEmitsClientOnce(): void
    {
        $this->setEnv('APP_ENV', 'development');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'dev', 'viteDevServer' => 'http://localhost:5173']);

        $js = viteJs('source/js/app.js', 'assets/build/js/app.js');
        $css = viteCss('source/scss/app.scss', 'assets/build/css/app.css');

        $this->assertStringContainsString('@vite/client', $js);
        $this->assertSame(1, substr_count($js . $css, '@vite/client'));
    }

    /**
     * CSS-first then JS must share a single HMR client tag.
     *
     * @return void
     */
    public function testCssThenJsSharesSingleClient(): void
    {
        $this->setEnv('APP_ENV', 'development');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'dev', 'viteDevServer' => 'http://localhost:5173']);

        $css = viteCss('source/scss/app.scss', 'assets/build/css/app.css');
        $js = viteJs('source/js/app.js', 'assets/build/js/app.js');

        $this->assertStringContainsString('@vite/client', $css);
        $this->assertSame(1, substr_count($css . $js, '@vite/client'));
    }

    /**
     * Dev URLs must be escaped so source values cannot inject markup.
     *
     * @return void
     */
    public function testDevUrlsAreEscaped(): void
    {
        $this->setEnv('APP_ENV', 'development');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'dev', 'viteDevServer' => 'http://localhost:5173']);

        $html = viteJs('source/js/a".js', 'assets/build/js/app.js');

        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringNotContainsString('a".js', $html);
    }

    /**
     * Prod manifest URLs must be escaped.
     *
     * @return void
     */
    public function testProdManifestUrlsAreEscaped(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => '1.0.0', 'viteDevServer' => '']);
        setViteManifestForTests(['source/js/app.js' => ['file' => 'js/app-"><x.js']]);

        $html = viteJs('source/js/app.js', 'assets/build/js/app.js');

        $this->assertStringContainsString('type="module"', $html);
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringNotContainsString('"><x', $html);
    }
}
