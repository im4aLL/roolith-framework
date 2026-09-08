<?php
namespace Tests;

use App\Core\ConfigValidator;
use App\Core\Exceptions\Exception as AppException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers ConfigValidator failure messages via isolated Config state.
 */
class ConfigValidatorTest extends TestCase
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
     * Original ROOLITH_ENVIRONMENT process value for restore.
     *
     * @var string|false
     */
    private string|false $originalEnv = false;

    /**
     * Whether this test defined APP_ROOT.
     *
     * @var bool
     */
    private bool $definedAppRoot = false;

    /**
     * Whether this test defined ROOLITH_CONFIG_ROOT.
     *
     * @var bool
     */
    private bool $definedConfigRoot = false;

    /**
     * Snapshot Config singleton state before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
            $this->definedAppRoot = true;
        }

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
            $this->definedConfigRoot = true;
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
     * Restore Config singleton state after each test.
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
    }

    /**
     * Missing baseUrl must throw a message naming baseUrl and APP_URL.
     *
     * @return void
     */
    public function testMissingBaseUrlThrowsHelpfulMessage(): void
    {
        $ref = new ReflectionClass(Config::class);

        // Keep a non-null instance so Config::get() does not reload from disk.
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => []]);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for missing baseUrl.');
        } catch (AppException $e) {
            $this->assertStringContainsString('baseUrl', $e->getMessage());
            $this->assertStringContainsString('APP_URL', $e->getMessage());
        }
    }

    /**
     * Non-array database must throw a message naming database.
     *
     * @return void
     */
    public function testNonArrayDatabaseThrowsHelpfulMessage(): void
    {
        $ref = new ReflectionClass(Config::class);

        // Keep a non-null instance so Config::get() does not reload from disk.
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => [
            'baseUrl' => 'http://localhost:8080/',
            'database' => 'not-an-array',
            'version' => '1.0.0',
            'forceNonWww' => true,
            'logPath' => '/tmp/app.log',
        ]]);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for non-array database.');
        } catch (AppException $e) {
            $this->assertStringContainsString('database', $e->getMessage());
        }
    }

    /**
     * Empty version string must throw a message naming version.
     *
     * @return void
     */
    public function testEmptyVersionThrowsHelpfulMessage(): void
    {
        $ref = new ReflectionClass(Config::class);

        // Keep a non-null instance so Config::get() does not reload from disk.
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => [
            'baseUrl' => 'http://localhost:8080/',
            'database' => null,
            'version' => '',
            'forceNonWww' => true,
            'logPath' => '/tmp/app.log',
        ]]);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for empty version.');
        } catch (AppException $e) {
            $this->assertStringContainsString('version', $e->getMessage());
        }
    }

    /**
     * Non-boolean forceNonWww must throw a message naming forceNonWww.
     *
     * @return void
     */
    public function testNonBoolForceNonWwwThrowsHelpfulMessage(): void
    {
        $ref = new ReflectionClass(Config::class);

        // Keep a non-null instance so Config::get() does not reload from disk.
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => [
            'baseUrl' => 'http://localhost:8080/',
            'database' => null,
            'version' => '1.0.0',
            'forceNonWww' => 'yes',
            'logPath' => '/tmp/app.log',
        ]]);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for non-bool forceNonWww.');
        } catch (AppException $e) {
            $this->assertStringContainsString('forceNonWww', $e->getMessage());
        }
    }

    /**
     * Seed isolated Config state for logPath cases.
     *
     * @param array<string, mixed> $defaultConfig Config values for the default env.
     * @return void
     */
    private function seedConfig(array $defaultConfig): void
    {
        $ref = new ReflectionClass(Config::class);

        // Keep a non-null instance so Config::get() does not reload from disk.
        $dummy = $ref->newInstanceWithoutConstructor();

        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, $dummy);

        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $configProp->setValue(null, ['default' => $defaultConfig]);
    }

    /**
     * Base valid config for logPath cases.
     *
     * @return array<string, mixed>
     */
    private function validBaseConfig(): array
    {
        return [
            'baseUrl' => 'http://localhost:8080/',
            'database' => null,
            'version' => '1.0.0',
            'forceNonWww' => true,
        ];
    }

    /**
     * Base valid config including log settings.
     *
     * @return array<string, mixed>
     */
    private function validFullConfig(): array
    {
        return [
            'baseUrl' => 'http://localhost:8080/',
            'database' => null,
            'version' => '1.0.0',
            'forceNonWww' => true,
            'logPath' => '/tmp/app.log',
            'logEnabled' => false,
        ];
    }

    /**
     * Missing logPath is optional and must pass validation.
     *
     * The minimal config.php omits logPath; Logger::defaultLogPath() supplies the default.
     *
     * @return void
     */
    public function testMissingLogPathIsOptional(): void
    {
        $this->seedConfig($this->validBaseConfig());

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    /**
     * Empty logPath must throw a message naming logPath.
     *
     * @return void
     */
    public function testEmptyLogPathThrowsHelpfulMessage(): void
    {
        $config = $this->validBaseConfig();
        $config['logPath'] = '   ';
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for empty logPath.');
        } catch (AppException $e) {
            $this->assertStringContainsString('logPath', $e->getMessage());
        }
    }

    /**
     * Traversal in logPath must throw a message naming logPath.
     *
     * @return void
     */
    public function testLogPathTraversalThrowsHelpfulMessage(): void
    {
        $config = $this->validBaseConfig();
        $config['logPath'] = '/tmp/../etc/app.log';
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for traversal logPath.');
        } catch (AppException $e) {
            $this->assertStringContainsString('logPath', $e->getMessage());
        }
    }

    /**
     * Null bytes in logPath must throw a message naming logPath.
     *
     * @return void
     */
    public function testLogPathNullByteThrowsHelpfulMessage(): void
    {
        $config = $this->validBaseConfig();
        $config['logPath'] = "/tmp/app\0.log";
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for null-byte logPath.');
        } catch (AppException $e) {
            $this->assertStringContainsString('logPath', $e->getMessage());
        }
    }

    /**
     * Missing logEnabled is optional and must pass validation.
     *
     * The minimal config.php omits logEnabled; Logger::defaultLogEnabled() supplies the default.
     *
     * @return void
     */
    public function testMissingLogEnabledIsOptional(): void
    {
        $config = $this->validFullConfig();
        unset($config['logEnabled']);
        $this->seedConfig($config);

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    /**
     * Non-boolean logEnabled must throw a message naming logEnabled.
     *
     * @return void
     */
    public function testNonBoolLogEnabledThrowsHelpfulMessage(): void
    {
        $config = $this->validFullConfig();
        $config['logEnabled'] = 'yes';
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for non-bool logEnabled.');
        } catch (AppException $e) {
            $this->assertStringContainsString('logEnabled', $e->getMessage());
        }
    }
}
