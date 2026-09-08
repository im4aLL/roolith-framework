<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers stable versioning and hashed asset helpers.
 */
class VersionTest extends TestCase
{
    /**
     * Original Config state for restore.
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
     * Env keys touched.
     *
     * @var array<int, string>
     */
    private const KEYS = ['APP_ENV', 'APP_VERSION'];

    /**
     * Backed up env values.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Backed up existence flags.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Backed up getenv values.
     *
     * @var array<string, string|false>
     */
    private array $getenvBackup = [];

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

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', APP_ROOT . '/views');
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

        foreach (self::KEYS as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
            $this->getenvBackup[$key] = getenv($key);
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

        foreach (self::KEYS as $key) {
            if ($this->envExists[$key]) {
                $_ENV[$key] = $this->envBackup[$key];
            } else {
                unset($_ENV[$key]);
            }

            unset($_SERVER[$key]);

            $original = $this->getenvBackup[$key];

            if ($original === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $original);
            }
        }

        setViteManifestForTests(null);
        resetViteClientTagForTests();
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
     * Explicit APP_VERSION must win.
     *
     * @return void
     */
    public function testExplicitVersionWins(): void
    {
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => '9.9.9']);

        $this->assertSame('9.9.9', getVersion());
    }

    /**
     * Asset URL must be stable in prod (same across calls).
     *
     * @return void
     */
    public function testAssetUrlStableInProd(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'abc1234', 'viteDevServer' => '']);

        $first = viteBuiltAssetUrl('assets/css/app.css');
        $second = viteBuiltAssetUrl('assets/css/app.css');

        $this->assertSame($first, $second);
        $this->assertSame('http://localhost:8080/assets/css/app.css?v=abc1234', $first);
    }

    /**
     * Manifest entries must resolve to hashed files without query.
     *
     * @return void
     */
    public function testManifestResolvesHashedFile(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'abc1234', 'viteDevServer' => '']);
        setViteManifestForTests([
            'source/js/app.js' => ['file' => 'js/app-abc123.js'],
            'source/scss/app.scss' => ['file' => 'css/app-def456.css'],
        ]);

        $this->assertSame('assets/js/app-abc123.js', viteManifestFile('source/js/app.js', 'assets/js/app.js'));
        $this->assertStringContainsString('js/app-abc123.js', viteJs('source/js/app.js', 'assets/js/app.js'));
        $this->assertStringNotContainsString('?v=', viteJs('source/js/app.js', 'assets/js/app.js'));
        $this->assertStringContainsString('css/app-def456.css', viteCss('source/scss/app.scss', 'assets/css/app.css'));
    }

    /**
     * Missing manifest must fall back to stable built path plus version.
     *
     * @return void
     */
    public function testMissingManifestFallsBack(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => 'abc1234', 'viteDevServer' => '']);
        setViteManifestForTests([]);

        $this->assertSame('assets/js/app.js', viteManifestFile('source/js/app.js', 'assets/js/app.js'));
        $this->assertStringContainsString('?v=abc1234', viteJs('source/js/app.js', 'assets/js/app.js'));
    }

    /**
     * setViteManifestForTests(null) must clear both override and file cache.
     *
     * Writes a real manifest under APP_ROOT/assets/.vite, reads it (warming
     * the $GLOBALS file cache), rewrites it, then proves the stale cache
     * is returned until cleared and the fresh file is returned after
     * clearing. Fails on the old static-$cache implementation where null
     * only cleared the override.
     *
     * @return void
     */
    public function testViteManifestCacheClearedByNull(): void
    {
        $base = (string) APP_ROOT;
        $dir = $base . '/assets/.vite';
        $file = $dir . '/manifest.json';
        $createdDir = false;
        $createdFile = !is_file($file);
        $backup = is_file($file) ? file_get_contents($file) : null;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            $createdDir = true;
        }

        try {
            setViteManifestForTests(null);
            file_put_contents($file, (string) json_encode(['source/js/app.js' => ['file' => 'js/app-first.js']]));

            $first = viteManifest();
            $this->assertSame('js/app-first.js', $first['source/js/app.js']['file'] ?? null);

            file_put_contents($file, (string) json_encode(['source/js/app.js' => ['file' => 'js/app-second.js']]));

            $stale = viteManifest();
            $this->assertSame('js/app-first.js', $stale['source/js/app.js']['file'] ?? null, 'Second read without clearing must use the cache.');

            setViteManifestForTests(null);

            $fresh = viteManifest();
            $this->assertSame('js/app-second.js', $fresh['source/js/app.js']['file'] ?? null, 'Clearing via null must drop the file cache so the next read re-reads disk.');
        } finally {
            if ($backup !== null && $backup !== false) {
                file_put_contents($file, (string) $backup);
            } elseif ($createdFile && is_file($file)) {
                @unlink($file);
            }

            if ($createdDir) {
                @rmdir($dir);
                @rmdir($base . '/assets');
            }

            setViteManifestForTests(null);
        }
    }

    /**
     * viteClientTag() must emit once until reset for tests.
     *
     * @return void
     */
    public function testViteClientTagResetSeam(): void
    {
        resetViteClientTagForTests();

        $first = viteClientTag('http://localhost:5173');
        $second = viteClientTag('http://localhost:5173');

        $this->assertStringContainsString('@vite/client', $first);
        $this->assertSame('', $second);

        resetViteClientTagForTests();

        $third = viteClientTag('http://localhost:5173');

        $this->assertStringContainsString('@vite/client', $third);

        resetViteClientTagForTests();
    }
}
