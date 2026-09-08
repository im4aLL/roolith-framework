<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers trusted-proxy IP resolution against spoofed headers.
 */
class TrustedProxyTest extends TestCase
{
    /**
     * Server keys touched by IP tests.
     *
     * @var array<int, string>
     */
    private const SERVER_KEYS = [
        'REMOTE_ADDR',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
    ];

    /**
     * Original server values for restore.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Whether each server key existed before the test.
     *
     * @var array<string, bool>
     */
    private array $serverExists = [];

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
     * Snapshot state and ensure helpers are loaded.
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

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        if (!function_exists('getIpAddress')) {
            require_once APP_ROOT . '/app/Utils/functions.php';
        }

        foreach (self::SERVER_KEYS as $key) {
            $this->serverExists[$key] = array_key_exists($key, $_SERVER);
            $this->serverBackup[$key] = $this->serverExists[$key] ? $_SERVER[$key] : null;
            unset($_SERVER[$key]);
        }

        $ref = new ReflectionClass(Config::class);

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $this->originalInstance = $instanceProp->getValue();

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $this->originalConfigArray = $configProp->getValue();

        $this->originalEnv = getenv(Config::ENV_KEY);
    }

    /**
     * Restore server and Config state.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (self::SERVER_KEYS as $key) {
            if ($this->serverExists[$key]) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }
        }

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
    }

    /**
     * Seed isolated Config state with a trusted-proxies list.
     *
     * @param array<int, string> $proxies Trusted proxy IPs.
     * @return void
     */
    private function seedTrustedProxies(array $proxies): void
    {
        $ref = new ReflectionClass(Config::class);
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => ['trustedProxies' => $proxies]]);
    }

    /**
     * Spoofed headers must be ignored when the remote is untrusted.
     *
     * @return void
     */
    public function testSpoofedHeadersIgnoredWhenProxyUntrusted(): void
    {
        $this->seedTrustedProxies([]);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.9';
        $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.10';

        $this->assertSame('203.0.113.5', getIpAddress());
    }

    /**
     * First forwarded IP must win when the remote is a trusted proxy.
     *
     * @return void
     */
    public function testForwardedHonoredWhenProxyTrusted(): void
    {
        $this->seedTrustedProxies(['10.0.0.1']);

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.9, 203.0.113.1';

        $this->assertSame('198.51.100.9', getIpAddress());
    }

    /**
     * Invalid forwarded values must fall back to the remote address.
     *
     * @return void
     */
    public function testInvalidForwardedFallsBackToRemote(): void
    {
        $this->seedTrustedProxies(['10.0.0.1']);

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-an-ip';

        $this->assertSame('10.0.0.1', getIpAddress());
    }

    /**
     * Missing remote address must fall back to localhost.
     *
     * @return void
     */
    public function testMissingRemoteFallsBackToLocalhost(): void
    {
        $this->seedTrustedProxies([]);

        $this->assertSame('127.0.0.1', getIpAddress());
    }
}
