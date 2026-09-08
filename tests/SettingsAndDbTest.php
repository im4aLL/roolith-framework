<?php
namespace Tests;

use App\Core\DatabaseFactory;
use App\Core\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers timezone/locale configurability and DB debug tie to APP_ENV.
 */
class SettingsAndDbTest extends TestCase
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
     * Env keys touched by these tests.
     *
     * @var array<int, string>
     */
    private const KEYS = ['APP_TIMEZONE', 'APP_LOCALE', 'APP_ENV'];

    /**
     * Backed up $_ENV values.
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
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        DatabaseFactory::resetForTests();
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

        DatabaseFactory::resetForTests();
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
     * Timezone must default to UTC when unconfigured or invalid.
     *
     * @return void
     */
    public function testTimezoneDefaultsToUtc(): void
    {
        $this->seedConfig([]);

        $this->assertSame('UTC', Settings::defaultTimezone());

        $this->setEnv('APP_TIMEZONE', 'Bogus/Zone');

        $this->assertSame('UTC', Settings::defaultTimezone());
    }

    /**
     * Timezone must honor Env when valid.
     *
     * @return void
     */
    public function testTimezoneHonorsEnv(): void
    {
        $this->seedConfig([]);
        $this->setEnv('APP_TIMEZONE', 'America/Chicago');

        $this->assertSame('America/Chicago', Settings::defaultTimezone());
    }

    /**
     * Locale must default to en and honor Env.
     *
     * @return void
     */
    public function testLocaleDefaultsAndHonorsEnv(): void
    {
        $this->seedConfig([]);

        $this->assertSame('en', Settings::defaultLocale());

        $this->setEnv('APP_LOCALE', 'en');

        $this->assertSame('en', Settings::defaultLocale());
    }

    /**
     * DB debug must follow APP_ENV (dev on, prod off).
     *
     * @return void
     */
    public function testDbDebugFollowsEnv(): void
    {
        $this->setEnv('APP_ENV', 'development');

        $this->assertTrue(DatabaseFactory::isDebugEnabled());

        $this->setEnv('APP_ENV', 'production');

        $this->assertFalse(DatabaseFactory::isDebugEnabled());
    }

    /**
     * DB factory must return fresh instances after reset.
     *
     * @return void
     */
    public function testDbFactoryResetGivesFreshInstance(): void
    {
        $first = DatabaseFactory::getInstance();
        DatabaseFactory::resetForTests();
        $second = DatabaseFactory::getInstance();

        $this->assertNotSame($first, $second);
    }

    /**
     * DB factory must not reapply the env default over a per-request override.
     *
     * Uses a DatabaseInterface spy so no real connection is needed: after
     * the singleton is armed, later getInstance() calls must return the
     * same instance without calling debugMode() again.
     *
     * @return void
     */
    public function testDbFactoryPreservesPerRequestOverride(): void
    {
        $spy = $this->createMock(\Roolith\Store\Interfaces\DatabaseInterface::class);
        $spy->expects($this->never())->method('debugMode');

        $ref = new ReflectionClass(DatabaseFactory::class);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue(null, $spy);

        try {
            $first = DatabaseFactory::getInstance();
            $second = DatabaseFactory::getInstance();

            $this->assertSame($spy, $first);
            $this->assertSame($spy, $second);
        } finally {
            $prop->setValue(null, null);
            DatabaseFactory::resetForTests();
        }
    }

    /**
     * Timezone apply must honor Env and update the process default.
     *
     * @return void
     */
    public function testApplyDefaultTimezoneHonorsEnv(): void
    {
        $previous = date_default_timezone_get();

        try {
            $this->seedConfig([]);
            $this->setEnv('APP_TIMEZONE', 'America/Chicago');

            $applied = \App\Core\Settings::applyDefaultTimezone();

            $this->assertSame('America/Chicago', $applied);
            $this->assertSame('America/Chicago', date_default_timezone_get());
            $this->assertSame('America/Chicago', \App\Core\Settings::defaultTimezone());
        } finally {
            date_default_timezone_set($previous);
        }
    }
}
