<?php
namespace Tests;

use App\Controllers\Controller;
use App\Core\ErrorHandler;
use App\Core\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Covers generic error pages with correct status codes.
 */
class ErrorPagesTest extends TestCase
{
    /**
     * Env keys snapshotted around each test.
     *
     * @var array<int, string>
     */
    private const ENV_KEYS = ['APP_ENV', 'LOG_PATH'];

    /**
     * Server keys snapshotted around each test.
     *
     * @var array<int, string>
     */
    private const SERVER_KEYS = ['REQUEST_METHOD', 'REQUEST_URI', 'HTTP_HOST'];

    /**
     * Original env values for restore.
     *
     * @var array<string, mixed>
     */
    private array $envBackup = [];

    /**
     * Whether each env key existed before the test.
     *
     * @var array<string, bool>
     */
    private array $envExists = [];

    /**
     * Original getenv values for restore.
     *
     * @var array<string, string|false>
     */
    private array $getenvBackup = [];

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
     * Isolated temp directory for log files.
     *
     * @var string
     */
    private string $tmpDir;

    /**
     * Snapshot state and ensure framework constants exist.
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

        if (!function_exists('redirect')) {
            require_once APP_ROOT . '/app/Utils/functions.php';
        }

        foreach (self::ENV_KEYS as $key) {
            $this->envExists[$key] = array_key_exists($key, $_ENV);
            $this->envBackup[$key] = $this->envExists[$key] ? $_ENV[$key] : null;
            $this->getenvBackup[$key] = getenv($key);
        }

        foreach (self::SERVER_KEYS as $key) {
            $this->serverExists[$key] = array_key_exists($key, $_SERVER);
            $this->serverBackup[$key] = $this->serverExists[$key] ? $_SERVER[$key] : null;
        }

        $this->tmpDir = sys_get_temp_dir() . '/roolith-errorpages-' . uniqid('', true);
        mkdir($this->tmpDir, 0775, true);
    }

    /**
     * Restore state and remove the temp directory.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $key) {
            if ($this->envExists[$key]) {
                $_ENV[$key] = $this->envBackup[$key];
            } else {
                unset($_ENV[$key]);
            }

            $original = $this->getenvBackup[$key];

            if ($original === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $original);
            }
        }

        foreach (self::SERVER_KEYS as $key) {
            if ($this->serverExists[$key]) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }
        }

        if (!headers_sent()) {
            http_response_code(200);
        }

        $files = glob($this->tmpDir . '/*') ?: [];

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
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
     * Prod must log the full trace while the body stays generic.
     *
     * @return void
     */
    public function testProdLogsTraceWhileBodyStaysGeneric(): void
    {
        $this->setKey('APP_ENV', 'production');

        $logFile = $this->tmpDir . '/prod.log';
        $logger = new Logger($logFile, 'trace-pages');

        $app = $this->createMock(\App\Core\System::class);
        $app->method('getTraceId')->willReturn('trace-pages');
        $app->method('getLogger')->willReturn($logger);

        ob_start();

        try {
            ErrorHandler::handle($app, new RuntimeException('secret detail'));
            $output = (string) ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }

        $this->assertSame(500, http_response_code());
        $this->assertStringContainsString('trace-pages', $output);
        $this->assertStringNotContainsString('secret detail', $output);

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringContainsString('secret detail', $contents);
        $this->assertStringContainsString('trace', $contents);
    }

    /**
     * Missing views must throw with context instead of echoing with 200.
     *
     * @return void
     */
    public function testMissingViewThrowsWithoutEcho(): void
    {
        $controller = new Controller();

        ob_start();

        try {
            $controller->view('__missing_view_xyz');
            $output = (string) ob_get_clean();
            $this->fail('Expected App exception for missing view.');
        } catch (\App\Core\Exceptions\Exception $e) {
            $output = (string) ob_get_clean();
        }

        $this->assertSame('', $output);
        $this->assertStringContainsString('__missing_view_xyz', $e->getMessage());
        $this->assertNotNull($e->getPrevious());
    }

    /**
     * Unknown routes must render views/404.php with a 404 code.
     *
     * @return void
     */
    public function testUnknownRouteRenders404WithCorrectCode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/definitely-missing-xyz';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $router = new \Roolith\Route\Router();
        $router->setBaseUrl('http://localhost:8080/');
        $router->setViewDir(APP_VIEW_ROOT);

        ob_start();
        $router->run();
        $output = (string) ob_get_clean();

        $this->assertSame(404, http_response_code());
        $this->assertStringContainsString('Page not found', $output);
    }

    /**
     * Redirect helpers must default to deliberate temporary codes.
     *
     * 303 implements Post/Redirect/Get (always follows with GET); the
     * Request helper keeps the legacy 302. Both are explicit choices,
     * not accidents.
     *
     * @return void
     */
    public function testRedirectDefaultsAreDeliberate(): void
    {
        $helpers = new \ReflectionFunction('redirect');
        $params = $helpers->getParameters();

        $this->assertSame(303, $params[1]->getDefaultValue());

        $routeHelper = new \ReflectionFunction('redirectToRoute');

        $this->assertSame(303, $routeHelper->getParameters()[2]->getDefaultValue());
    }
}
