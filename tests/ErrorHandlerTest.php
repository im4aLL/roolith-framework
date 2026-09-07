<?php
namespace Tests;

use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\System;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Covers ErrorHandler prod/dev branches, null-app fallback, and trace correlation.
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * Env keys snapshotted around each test.
     *
     * @var array<int, string>
     */
    private const KEYS = ['APP_ENV', 'LOG_PATH'];

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
     * Isolated temp directory for log files.
     *
     * @var string
     */
    private string $tmpDir;

    /**
     * Snapshot env state and create an isolated temp directory.
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

        $this->tmpDir = sys_get_temp_dir() . '/roolith-errorhandler-' . uniqid('', true);
        mkdir($this->tmpDir, 0775, true);
    }

    /**
     * Restore env state and remove the isolated temp directory.
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
     * Prod must echo an escaped trace and log the same trace ID.
     *
     * @return void
     */
    public function testProdOutputsEscapedTraceAndCorrelatesLog(): void
    {
        $this->setKey('APP_ENV', 'production');
        $this->unsetKey('LOG_PATH');

        $logFile = $this->tmpDir . '/prod.log';
        $traceId = 'trace-<b>&"\'test</b>';
        $escaped = htmlspecialchars($traceId, ENT_QUOTES, 'UTF-8');
        $logger = new Logger($logFile, $traceId);

        $app = $this->createMock(System::class);
        $app->method('getTraceId')->willReturn($traceId);
        $app->method('getLogger')->willReturn($logger);

        ob_start();

        try {
            ErrorHandler::handle($app, new RuntimeException('boom'));
            $output = (string) ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }

        $this->assertStringContainsString($escaped, $output);
        $this->assertStringNotContainsString($traceId, $output);

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringContainsString($traceId, $contents);
        $this->assertSame(500, http_response_code());
    }

    /**
     * Null app must write to LOG_PATH and echo the same trace ID.
     *
     * @return void
     */
    public function testNullAppFallbackWritesToLogPathWithMatchingTrace(): void
    {
        $this->setKey('APP_ENV', 'production');
        $logFile = $this->tmpDir . '/fallback.log';
        $this->setKey('LOG_PATH', $logFile);

        ob_start();

        try {
            ErrorHandler::handle(null, new RuntimeException('null app boom'));
            $output = (string) ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }

        $this->assertMatchesRegularExpression('/Internal Server Error \(trace: ([^)]+)\)/', $output);
        $this->assertSame(1, preg_match('/Internal Server Error \(trace: ([^)]+)\)/', $output, $matches));

        $traceFromOutput = $matches[1];

        $this->assertStringContainsString(htmlspecialchars($traceFromOutput, ENT_QUOTES, 'UTF-8'), $output);
        $this->assertFileExists($logFile);

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringContainsString($traceFromOutput, $contents);
        $this->assertStringContainsString('null app boom', $contents);
        $this->assertSame(500, http_response_code());
    }

    /**
     * Development must rethrow the original exception unchanged.
     *
     * @return void
     */
    public function testDevRethrowsOriginalException(): void
    {
        $this->setKey('APP_ENV', 'development');
        $this->setKey('LOG_PATH', $this->tmpDir . '/dev.log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('dev boom');

        ErrorHandler::handle(null, new RuntimeException('dev boom'));
    }
}
