<?php
namespace Tests;

use App\Core\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Covers file logging, newline stripping, and never-throw behavior.
 */
class LoggerTest extends TestCase
{
    /**
     * Isolated temp directory for log files.
     *
     * @var string
     */
    private string $tmpDir;

    /**
     * Env key snapshotted around each test.
     *
     * @var string
     */
    private const ENV_KEY = 'LOG_PATH';

    /**
     * Whether LOG_PATH existed in $_ENV before the test.
     *
     * @var bool
     */
    private bool $envExists = false;

    /**
     * Original $_ENV value for restore.
     *
     * @var mixed
     */
    private mixed $envBackup = null;

    /**
     * Whether LOG_PATH existed in $_SERVER before the test.
     *
     * @var bool
     */
    private bool $serverExists = false;

    /**
     * Original $_SERVER value for restore.
     *
     * @var mixed
     */
    private mixed $serverBackup = null;

    /**
     * Original getenv value for restore.
     *
     * @var string|false
     */
    private string|false $getenvBackup = false;

    /**
     * Create an isolated temp directory.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/roolith-logger-' . uniqid('', true);
        mkdir($this->tmpDir, 0775, true);

        $key = self::ENV_KEY;
        $this->envExists = array_key_exists($key, $_ENV);
        $this->envBackup = $this->envExists ? $_ENV[$key] : null;
        $this->serverExists = array_key_exists($key, $_SERVER);
        $this->serverBackup = $this->serverExists ? $_SERVER[$key] : null;
        $this->getenvBackup = getenv($key);
    }

    /**
     * Remove the isolated temp directory.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $key = self::ENV_KEY;

        if ($this->envExists) {
            $_ENV[$key] = $this->envBackup;
        } else {
            unset($_ENV[$key]);
        }

        if ($this->serverExists) {
            $_SERVER[$key] = $this->serverBackup;
        } else {
            unset($_SERVER[$key]);
        }

        if ($this->getenvBackup === false) {
            putenv($key);
        } else {
            putenv($key . '=' . $this->getenvBackup);
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
     * Set LOG_PATH in all env sources.
     *
     * @param string $value Value to assign.
     * @return void
     */
    private function setLogPath(string $value): void
    {
        $_ENV[self::ENV_KEY] = $value;
        $_SERVER[self::ENV_KEY] = $value;
        putenv(self::ENV_KEY . '=' . $value);
    }

    /**
     * Clear LOG_PATH from all env sources.
     *
     * @return void
     */
    private function unsetLogPath(): void
    {
        unset($_ENV[self::ENV_KEY], $_SERVER[self::ENV_KEY]);
        putenv(self::ENV_KEY);
    }

    /**
     * Log lines must include the trace ID, message, and level.
     *
     * @return void
     */
    public function testWritesLineWithTraceId(): void
    {
        $logFile = $this->tmpDir . '/app.log';
        $logger = new Logger($logFile, 'trace-123');

        $logger->info('hello bootstrap');

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringContainsString('trace-123', $contents);
        $this->assertStringContainsString('hello bootstrap', $contents);
        $this->assertStringContainsString('INFO', $contents);
    }

    /**
     * Carriage returns and line feeds must be flattened to spaces.
     *
     * @return void
     */
    public function testStripsCarriageReturnAndLineFeed(): void
    {
        $logFile = $this->tmpDir . '/app.log';
        $logger = new Logger($logFile, 'trace-abc');

        $logger->info("line one\r\nline two\nline three");

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringNotContainsString("\r", $contents);
        $this->assertStringNotContainsString("\n", trim($contents));
        $this->assertStringContainsString('line one', $contents);
        $this->assertStringContainsString('line two', $contents);
        $this->assertStringContainsString('line three', $contents);
    }

    /**
     * Unwritable paths must fall back to error_log instead of throwing.
     *
     * @return void
     */
    public function testNeverThrowsOnUnwritablePath(): void
    {
        // Block directory creation: parent path is a file, not a directory.
        $blocker = $this->tmpDir . '/blocker';
        file_put_contents($blocker, 'x');
        $logger = new Logger($blocker . '/nested/app.log', 'trace-fail');

        try {
            $logger->info('should not throw');
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('Logger must never throw, got: ' . get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * LOG_PATH override must win over the default path.
     *
     * @return void
     */
    public function testDefaultLogPathUsesLogPathOverride(): void
    {
        $override = $this->tmpDir . '/custom.log';
        $this->setLogPath($override);

        $this->assertSame(str_replace('\\', '/', $override), Logger::defaultLogPath());
    }

    /**
     * Unset LOG_PATH must fall back to storage/logs/app.log.
     *
     * @return void
     */
    public function testDefaultLogPathFallsBackToDefault(): void
    {
        $this->unsetLogPath();

        $this->assertStringEndsWith('/storage/logs/app.log', Logger::defaultLogPath());
    }

    /**
     * Traversal in LOG_PATH must fall back to the default path.
     *
     * @return void
     */
    public function testDefaultLogPathRejectsTraversal(): void
    {
        $this->setLogPath($this->tmpDir . '/../outside.log');

        $this->assertStringEndsWith('/storage/logs/app.log', Logger::defaultLogPath());
    }

    /**
     * Null byte in LOG_PATH must fall back to the default path.
     *
     * @return void
     */
    public function testDefaultLogPathRejectsNullByte(): void
    {
        $this->setLogPath($this->tmpDir . "/app\0.log");

        $this->assertStringEndsWith('/storage/logs/app.log', Logger::defaultLogPath());
    }

    /**
     * Disabled logger must drop info but still write errors.
     *
     * @return void
     */
    public function testDisabledDropsInfoButWritesError(): void
    {
        $logFile = $this->tmpDir . '/disabled.log';
        $logger = new Logger($logFile, 'trace-disabled', false);

        $logger->info('routine info');
        $logger->debug('routine debug');
        $logger->notice('routine notice');

        $this->assertFileDoesNotExist($logFile);

        $logger->warning('warn kept');
        $logger->error('error kept');

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('routine info', $contents);
        $this->assertStringNotContainsString('routine debug', $contents);
        $this->assertStringNotContainsString('routine notice', $contents);
        $this->assertStringContainsString('warn kept', $contents);
        $this->assertStringContainsString('error kept', $contents);
    }

    /**
     * Enabled logger must write routine info lines.
     *
     * @return void
     */
    public function testEnabledWritesInfo(): void
    {
        $logFile = $this->tmpDir . '/enabled.log';
        $logger = new Logger($logFile, 'trace-enabled', true);

        $logger->info('routine info');

        $contents = file_get_contents($logFile);

        $this->assertIsString($contents);
        $this->assertStringContainsString('routine info', $contents);
    }
}
