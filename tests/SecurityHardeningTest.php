<?php
namespace Tests;

use App\Core\ConfigValidator;
use App\Core\ErrorHandler;
use App\Core\Exceptions\Exception as AppException;
use App\Core\System;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Configuration\Config;

/**
 * Security hardening: cookie config validation, error-header reuse, upload
 * htaccess denies, and baseUrl validation.
 */
class SecurityHardeningTest extends TestCase
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
     * Snapshot Config state and ensure constants exist.
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
    }

    /**
     * Restore Config state.
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

        if (!headers_sent()) {
            http_response_code(200);
        }
    }

    /**
     * Seed isolated Config state.
     *
     * @param array<string, mixed> $defaultConfig Config for the default env.
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
     * Base valid config for validator cases.
     *
     * @return array<string, mixed> Valid config values.
     */
    private function validConfig(): array
    {
        return [
            'baseUrl' => 'http://localhost:8080/',
            'database' => null,
            'version' => '1.0.0',
            'forceNonWww' => true,
            'logPath' => '/tmp/app.log',
            'logEnabled' => false,
            'cookiePath' => '/',
            'cookieDomain' => '',
            'cookieSecure' => true,
            'cookieSameSite' => 'Lax',
            'sessionLifetime' => 0,
            'trustedProxies' => [],
            'securityHeaders' => true,
        ];
    }

    /**
     * SameSite=None without Secure must fail validation.
     *
     * @return void
     */
    public function testSameSiteNoneWithoutSecureFails(): void
    {
        $config = $this->validConfig();
        $config['cookieSameSite'] = 'None';
        $config['cookieSecure'] = false;
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for SameSite=None without Secure.');
        } catch (AppException $e) {
            $this->assertStringContainsString('cookieSameSite', $e->getMessage());
            $this->assertStringContainsString('cookieSecure', $e->getMessage());
        }
    }

    /**
     * SameSite=None with Secure must pass validation.
     *
     * @return void
     */
    public function testSameSiteNoneWithSecurePasses(): void
    {
        $config = $this->validConfig();
        $config['cookieSameSite'] = 'None';
        $config['cookieSecure'] = true;
        $this->seedConfig($config);

        ConfigValidator::validate();

        $this->assertTrue(true);
    }

    /**
     * Non-URL baseUrl values must fail validation.
     *
     * @return void
     */
    public function testInvalidBaseUrlFails(): void
    {
        $config = $this->validConfig();
        $config['baseUrl'] = 'not-a-url';
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for non-URL baseUrl.');
        } catch (AppException $e) {
            $this->assertStringContainsString('baseUrl', $e->getMessage());
        }
    }

    /**
     * Non-http schemes must fail baseUrl validation.
     *
     * @return void
     */
    public function testNonHttpBaseUrlSchemeFails(): void
    {
        $config = $this->validConfig();
        $config['baseUrl'] = 'ftp://example.com/';
        $this->seedConfig($config);

        try {
            ConfigValidator::validate();
            $this->fail('Expected AppException for ftp baseUrl.');
        } catch (AppException $e) {
            $this->assertStringContainsString('baseUrl', $e->getMessage());
        }
    }

    /**
     * Error 500 path must reuse the System baseline headers.
     *
     * @return void
     */
    public function testErrorHandlerReusesSecurityHeaders(): void
    {
        $this->assertSame(System::securityHeaders(), ErrorHandler::headersForErrorResponse());

        $source = (string) file_get_contents(APP_ROOT . '/app/Core/ErrorHandler.php');

        $this->assertStringContainsString('System::securityHeaders', $source);
        $this->assertStringContainsString('headers_sent', $source);
    }

    /**
     * Htaccess must block sensitive files case-insensitively.
     *
     * Covers Phase 4 F5: /installer.zip must 404 so the tracked binary stays
     * hidden over HTTP.
     *
     * @return void
     */
    public function testHtaccessBlocksSensitiveFiles(): void
    {
        $contents = (string) file_get_contents(APP_ROOT . '/.htaccess');

        $this->assertStringContainsString('(?i)', $contents);
        $this->assertStringContainsString('composer\\.json', $contents);
        $this->assertStringContainsString('phpunit\\.xml', $contents);
        $this->assertStringContainsString('\\.log', $contents);
        $this->assertStringContainsString('(\\..*)?', $contents);
        $this->assertStringContainsString('installer\\.zip', $contents);
    }
}
