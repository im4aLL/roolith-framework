<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Covers url() slash normalization and template replacement.
 */
class UrlAndTemplateTest extends TestCase
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
    private string|false $originalEnv = false;

    /**
     * Back up APP_ENV around each test.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Seed isolated config and load helpers before each test.
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
        $this->originalEnv = getenv(Config::ENV_KEY);

        $this->envBackup = [
            'APP_ENV' => [array_key_exists('APP_ENV', $_ENV) ? $_ENV['APP_ENV'] : null, array_key_exists('APP_ENV', $_ENV), getenv('APP_ENV')],
        ];

        $this->seedConfig(['baseUrl' => 'http://localhost:8080/', 'version' => '1']);
    }

    /**
     * Restore Config and Env after each test.
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

        [$value, $exists, $getenv] = $this->envBackup['APP_ENV'];

        if ($exists) {
            $_ENV['APP_ENV'] = $value;
        } else {
            unset($_ENV['APP_ENV']);
        }

        if ($getenv === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $getenv);
        }

        unset($_SERVER['APP_ENV']);
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
     * url() must normalize slashes on both sides.
     *
     * @return void
     */
    public function testUrlNormalizesSlashes(): void
    {
        $this->assertSame('http://localhost:8080/assets/css/app.css', url('assets/css/app.css'));
        $this->assertSame('http://localhost:8080/assets/css/app.css', url('/assets/css/app.css'));
        $this->assertSame('http://localhost:8080/', url(''));
        $this->assertSame('http://localhost:8080/', url('/'));
    }

    /**
     * url() with trailing-slash base must not double slashes.
     *
     * @return void
     */
    public function testUrlWithTrailingSlashBase(): void
    {
        $this->seedConfig(['baseUrl' => 'http://localhost:8080', 'version' => '1']);

        $this->assertSame('http://localhost:8080/a.css', url('/a.css'));
    }

    /**
     * parseBasicTemplate must treat keys literally (no regex injection).
     *
     * @return void
     */
    public function testParseBasicTemplateHandlesRegexChars(): void
    {
        $this->assertSame('hi X', parseBasicTemplate('hi {{a.b*c}}', ['a.b*c' => 'X']));
        $this->assertSame('hi [x]', parseBasicTemplate('hi {{key}}', ['key' => '[x]']));
        $this->assertSame('no-op', parseBasicTemplate('no-op', []));
    }
}
