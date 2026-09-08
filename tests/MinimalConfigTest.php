<?php
namespace Tests;

use App\Core\ConfigValidator;
use App\Core\Session;
use App\Core\Storage;
use App\Core\System;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Pins the minimal 5-key config shape and Env-only advanced defaults.
 *
 * The minimal config.php holds only baseUrl, viteDevServer, database,
 * forceNonWww, and version. Cookie and security-header keys are omitted
 * and must fall back in code directly from Env, so setting .env alone
 * takes effect without re-adding a config key.
 */
class MinimalConfigTest extends TestCase
{
    /**
     * Env keys touched by these tests.
     *
     * @var array<int, string>
     */
    private const ENV_KEYS = [
        'APP_URL',
        'COOKIE_PATH',
        'COOKIE_DOMAIN',
        'COOKIE_SECURE',
        'COOKIE_SAMESITE',
        'SESSION_LIFETIME',
        'SECURITY_HEADERS',
    ];

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
     * Original ROOLITH_ENVIRONMENT process value for restore.
     *
     * @var string|false
     */
    private string|false $originalEnv = false;

    /**
     * Original $_ENV values for restore.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Whether each key existed in $_ENV before the test.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Original $_SERVER values for restore.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Whether each key existed in $_SERVER before the test.
     *
     * @var array<string, bool>
     */
    private array $serverExists = [];

    /**
     * Original getenv values for restore.
     *
     * @var array<string, string|false>
     */
    private array $getenvBackup = [];

    /**
     * Snapshot Config and Env state before each test.
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

        $this->originalEnv = getenv(Config::ENV_KEY);

        foreach (self::ENV_KEYS as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
            $this->serverExists[$key] = array_key_exists($key, $_SERVER);
            $this->serverBackup[$key] = $this->serverExists[$key] ? $_SERVER[$key] : null;
            $this->getenvBackup[$key] = getenv($key);
        }
    }

    /**
     * Restore Config and Env state after each test.
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

        if ($this->originalEnv === false) {
            putenv(Config::ENV_KEY);
        } else {
            putenv(Config::ENV_KEY . '=' . $this->originalEnv);
        }

        foreach (self::ENV_KEYS as $key) {
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
     * Seed isolated Config state for the default env.
     *
     * @param array<string, mixed> $defaultConfig Config values for the default env.
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
     * Minimal 5-key config matching config/config.php.
     *
     * @return array<string, mixed>
     */
    private function minimalConfig(): array
    {
        return [
            'baseUrl' => 'http://localhost:8080/',
            'viteDevServer' => '',
            'database' => null,
            'forceNonWww' => true,
            'version' => '1.0.0',
        ];
    }

    /**
     * Set an env key in all sources.
     *
     * @param string $key Env key to set.
     * @param string $value Value to assign.
     * @return void
     */
    private function setEnvKey(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    /**
     * Clear an env key from all sources.
     *
     * @param string $key Env key to clear.
     * @return void
     */
    private function unsetEnvKey(string $key): void
    {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    /**
     * Clear all cookie and security-header Env overrides.
     *
     * @return void
     */
    private function clearCookieEnv(): void
    {
        foreach (['COOKIE_PATH', 'COOKIE_DOMAIN', 'COOKIE_SECURE', 'COOKIE_SAMESITE', 'SESSION_LIFETIME', 'SECURITY_HEADERS'] as $key) {
            $this->unsetEnvKey($key);
        }
    }

    /**
     * Real config.php must keep the 5-key minimal shape.
     *
     * @return void
     */
    public function testRealConfigFileHasFiveKeys(): void
    {
        $data = require APP_ROOT . '/config/config.php';

        $this->assertIsArray($data);
        $this->assertSame(['baseUrl', 'database', 'forceNonWww', 'version', 'viteDevServer'], $this->sortedKeys($data));
        $this->assertCount(5, $data);

        foreach (['cookiePath', 'cookieDomain', 'cookieSecure', 'cookieSameSite', 'sessionLifetime', 'securityHeaders', 'trustedProxies', 'logPath', 'logEnabled'] as $advanced) {
            $this->assertArrayNotHasKey($advanced, $data, 'Advanced key ' . $advanced . ' must stay out of the minimal config.');
        }
    }

    /**
     * Minimal config must validate and downstream defaults must apply.
     *
     * @return void
     */
    public function testMinimalConfigValidatesWithDownstreamDefaults(): void
    {
        $this->seedConfig($this->minimalConfig());
        $this->clearCookieEnv();
        $this->setEnvKey('APP_URL', 'http://localhost:8080/');

        ConfigValidator::validate();

        $params = Session::cookieParams();

        $this->assertSame(0, $params['lifetime']);
        $this->assertSame('/', $params['path']);
        $this->assertSame('', $params['domain']);
        $this->assertFalse($params['secure']);
        $this->assertTrue($params['httponly']);
        $this->assertSame('Lax', $params['samesite']);

        $options = Storage::cookieOptions(time() + 3600);

        $this->assertSame('/', $options['path']);
        $this->assertSame('', $options['domain']);
        $this->assertFalse($options['secure']);
        $this->assertTrue($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);

        $this->assertTrue(System::isSecurityHeadersEnabled());
    }

    /**
     * Setting .env alone must take effect with no config keys present.
     *
     * @return void
     */
    public function testEnvOverridesTakeEffectWithoutConfigKeys(): void
    {
        $this->seedConfig($this->minimalConfig());

        $this->setEnvKey('COOKIE_PATH', '/foo');
        $this->setEnvKey('COOKIE_DOMAIN', 'example.com');
        $this->setEnvKey('COOKIE_SECURE', '1');
        $this->setEnvKey('COOKIE_SAMESITE', 'Strict');
        $this->setEnvKey('SESSION_LIFETIME', '60');
        $this->setEnvKey('SECURITY_HEADERS', '0');

        $params = Session::cookieParams();

        $this->assertSame('/foo', $params['path']);
        $this->assertSame('example.com', $params['domain']);
        $this->assertTrue($params['secure']);
        $this->assertSame('Strict', $params['samesite']);
        $this->assertSame(60, $params['lifetime']);

        $options = Storage::cookieOptions(time() + 3600);

        $this->assertSame('/foo', $options['path']);
        $this->assertSame('example.com', $options['domain']);
        $this->assertTrue($options['secure']);
        $this->assertSame('Strict', $options['samesite']);

        $this->assertFalse(System::isSecurityHeadersEnabled());
    }

    /**
     * Unset env must fall back to hardcoded defaults.
     *
     * @return void
     */
    public function testUnsetEnvUsesHardcodedDefaults(): void
    {
        $this->seedConfig($this->minimalConfig());
        $this->clearCookieEnv();
        $this->setEnvKey('APP_URL', 'http://localhost:8080/');

        $params = Session::cookieParams();

        $this->assertSame('/', $params['path']);
        $this->assertSame('', $params['domain']);
        $this->assertSame(0, $params['lifetime']);
        $this->assertSame('Lax', $params['samesite']);
        $this->assertFalse($params['secure']);

        $options = Storage::cookieOptions(time() + 3600);

        $this->assertSame('/', $options['path']);
        $this->assertSame('', $options['domain']);
        $this->assertSame('Lax', $options['samesite']);

        $this->assertTrue(System::isSecurityHeadersEnabled());
    }

    /**
     * Explicit config values must win over Env overrides.
     *
     * @return void
     */
    public function testExplicitConfigWinsOverEnv(): void
    {
        $config = $this->minimalConfig();
        $config['cookiePath'] = '/config';
        $config['cookieSecure'] = false;
        $config['cookieSameSite'] = 'Lax';
        $config['sessionLifetime'] = 30;
        $config['securityHeaders'] = true;
        $this->seedConfig($config);

        $this->setEnvKey('COOKIE_PATH', '/foo');
        $this->setEnvKey('COOKIE_SECURE', '1');
        $this->setEnvKey('COOKIE_SAMESITE', 'Strict');
        $this->setEnvKey('SESSION_LIFETIME', '60');
        $this->setEnvKey('SECURITY_HEADERS', '0');

        $params = Session::cookieParams();

        $this->assertSame('/config', $params['path']);
        $this->assertFalse($params['secure']);
        $this->assertSame('Lax', $params['samesite']);
        $this->assertSame(30, $params['lifetime']);

        $this->assertTrue(System::isSecurityHeadersEnabled());
    }

    /**
     * Https baseUrl must default Secure on when no explicit flag exists.
     *
     * @return void
     */
    public function testHttpsBaseUrlDefaultsSecureOn(): void
    {
        $config = $this->minimalConfig();
        $config['baseUrl'] = 'https://example.com/';
        $this->seedConfig($config);
        $this->clearCookieEnv();
        $this->setEnvKey('APP_URL', 'https://example.com/');

        $this->assertTrue(Session::defaultSecure());
        $this->assertTrue(Session::cookieParams()['secure']);
        $this->assertTrue(Storage::cookieOptions(time() + 3600)['secure']);
    }

    /**
     * Sort array keys for stable comparison.
     *
     * @param array<string, mixed> $data Array whose keys to sort.
     * @return array<int, string> Sorted keys.
     */
    private function sortedKeys(array $data): array
    {
        $keys = array_keys($data);
        sort($keys);

        return $keys;
    }
}
