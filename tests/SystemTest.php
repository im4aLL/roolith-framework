<?php
namespace Tests;

use App\Core\DatabaseFactory;
use App\Core\RouterFactory;
use App\Core\System;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers System routes re-entry (016) and bootstrap chain (017).
 *
 * Re-entry: processRequest() must reset the shared RouterFactory singleton
 * before loading routes.php so two calls in one process register the same
 * count instead of doubling. Chain: bootstrap() must preserve the cause
 * via Exception(message, 0, previous) on every failure path.
 */
class SystemTest extends TestCase
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
     * Original ROOLITH_ENVIRONMENT value for restore.
     *
     * @var string|false
     */
    private string|false $originalConfigEnv = false;

    /**
     * Env backup keyed by name.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Env existence flags.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Getenv backup.
     *
     * @var array<string, string|false>
     */
    private array $getenvBackup = [];

    /**
     * Server backup.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Snapshot Config, Env, and facades before each test.
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

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        $ref = new ReflectionClass(Config::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $this->originalInstance = $instanceProp->getValue();
        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $this->originalConfigArray = $configProp->getValue();
        $this->originalConfigEnv = getenv(Config::ENV_KEY);

        foreach (['APP_ENV', 'APP_TIMEZONE'] as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
            $this->getenvBackup[$key] = getenv($key);
        }

        $this->serverBackup = $_SERVER;

        RouterFactory::resetForTests();
        DatabaseFactory::resetForTests();
    }

    /**
     * Restore Config, Env, and facades after each test.
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

        if ($this->originalConfigEnv === false) {
            putenv(Config::ENV_KEY);
        } else {
            putenv(Config::ENV_KEY . '=' . $this->originalConfigEnv);
        }

        foreach (['APP_ENV', 'APP_TIMEZONE'] as $key) {
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

        $_SERVER = $this->serverBackup;

        RouterFactory::resetForTests();
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
     * Double processRequest() must not duplicate route registrations (016).
     *
     * Calls processRequest() twice in one process (output buffered because
     * router run echoes) and asserts the route count is stable instead of
     * doubling on the shared RouterFactory singleton.
     *
     * @return void
     */
    public function testProcessRequestReEntryDoesNotDuplicateRoutes(): void
    {
        $this->setEnv('APP_ENV', 'testing');
        $this->seedConfig(['baseUrl' => 'http://localhost:8080/']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $_SERVER['REQUEST_URI'] = '/';

        $system = new System(new NullLogger());

        ob_start();
        try {
            $system->processRequest();
        } finally {
            ob_end_clean();
        }

        $firstCount = count(RouterFactory::getInstance()->getRouteList());
        $this->assertGreaterThan(0, $firstCount);

        ob_start();
        try {
            $system->processRequest();
        } finally {
            ob_end_clean();
        }

        $secondCount = count(RouterFactory::getInstance()->getRouteList());

        $this->assertSame($firstCount, $secondCount, 'Second processRequest() must reset the router instead of appending duplicates.');
    }

    /**
     * Bootstrap config failure must preserve the exception chain (017).
     *
     * Seeds an invalid baseUrl so ConfigValidator fails, then asserts the
     * thrown Exception carries a non-null previous cause.
     *
     * @return void
     */
    public function testBootstrapPreservesExceptionChainWithPrevious(): void
    {
        $this->setEnv('APP_ENV', 'testing');
        $this->seedConfig([
            'baseUrl' => '',
            'database' => null,
            'version' => 'test',
            'forceNonWww' => true,
        ]);

        $system = new System(new NullLogger());

        try {
            $system->bootstrap();
            $this->fail('Expected bootstrap to throw on invalid baseUrl.');
        } catch (\App\Core\Exceptions\Exception $e) {
            $this->assertNotNull($e->getPrevious(), 'Bootstrap must chain the cause via previous.');
            $this->assertStringContainsString('baseUrl', $e->getMessage());
        }
    }
}
