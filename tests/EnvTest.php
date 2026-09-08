<?php
namespace Tests;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

/**
 * Covers Env defaults, development detection, and value normalization.
 */
class EnvTest extends TestCase
{
    /**
     * Env keys snapshotted around each test.
     *
     * @var array<int, string>
     */
    private const KEYS = ['APP_ENV', 'ENV_TEST_EMPTY', 'ENV_TEST_ZERO'];

    /**
     * Whether each key existed in $_ENV before the test.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Original $_ENV values for restore.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Whether each key existed in $_SERVER before the test.
     *
     * @var array<string, bool>
     */
    private array $serverExists = [];

    /**
     * Original $_SERVER values for restore.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Original getenv values for restore.
     *
     * @var array<string, string|false>
     */
    private array $getenvBackup = [];

    /**
     * Snapshot env state before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
            $this->serverExists[$key] = array_key_exists($key, $_SERVER);
            $this->serverBackup[$key] = $this->serverExists[$key] ? $_SERVER[$key] : null;
            $this->getenvBackup[$key] = getenv($key);
        }
    }

    /**
     * Restore env state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            if ($this->envExists[$key]) {
                $_ENV[$key] = $this->envBackup[$key];
            } else {
                unset($_ENV[$key]);
            }

            if ($this->serverExists[$key]) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }

            $original = $this->getenvBackup[$key];

            if ($original === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $original);
            }
        }
    }

    /**
     * Clear an env key from all sources.
     *
     * @param string $key Env key to clear.
     * @return void
     */
    private function unsetKey(string $key): void
    {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    /**
     * Set an env key in all sources.
     *
     * @param string $key Env key to set.
     * @param string $value Value to assign.
     * @return void
     */
    private function setKey(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    /**
     * Unset APP_ENV must fail closed to production.
     *
     * @return void
     */
    public function testAppEnvDefaultsToProductionWhenUnset(): void
    {
        $this->unsetKey('APP_ENV');

        $this->assertSame('production', Env::appEnv());
        $this->assertTrue(Env::isProduction());
        $this->assertFalse(Env::isDevelopment());
    }

    /**
     * Exact `development` value must enable development mode.
     *
     * @return void
     */
    public function testDevelopmentDetection(): void
    {
        $this->setKey('APP_ENV', 'development');

        $this->assertSame('development', Env::appEnv());
        $this->assertTrue(Env::isDevelopment());
        $this->assertFalse(Env::isProduction());
    }

    /**
     * Empty string must count as unset and return the default.
     *
     * @return void
     */
    public function testGetTreatsEmptyStringAsUnset(): void
    {
        $this->setKey('ENV_TEST_EMPTY', '');

        $this->assertSame('fallback', Env::get('ENV_TEST_EMPTY', 'fallback'));
    }

    /**
     * String "0" must be preserved and not treated as empty.
     *
     * @return void
     */
    public function testGetPreservesZeroString(): void
    {
        $this->setKey('ENV_TEST_ZERO', '0');

        $this->assertSame('0', Env::get('ENV_TEST_ZERO', 'fallback'));
    }

    /**
     * is() must match the active env against any of the given names.
     *
     * @return void
     */
    public function testIsMatchesActiveEnv(): void
    {
        $this->setKey('APP_ENV', 'staging');

        $this->assertTrue(Env::is('staging', 'uat'));
        $this->assertFalse(Env::is('production', 'development'));
    }

    /**
     * is() must return false when the active env matches none of the names.
     *
     * @return void
     */
    public function testIsReturnsFalseWhenNoMatch(): void
    {
        $this->setKey('APP_ENV', 'production');

        $this->assertTrue(Env::is('production'));
        $this->assertFalse(Env::is('staging', 'uat'));
    }
}
