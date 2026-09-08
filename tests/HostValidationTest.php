<?php
namespace Tests;

use App\Core\PreProcessor;
use App\Core\Request;
use PHPUnit\Framework\TestCase;

/**
 * Covers host allowlisting for redirects and URL building.
 */
class HostValidationTest extends TestCase
{
    /**
     * Server keys touched by host tests.
     *
     * @var array<int, string>
     */
    private const KEYS = ['HTTP_HOST', 'REQUEST_URI', 'HTTPS'];

    /**
     * Original server values for restore.
     *
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * Whether each key existed before the test.
     *
     * @var array<string, bool>
     */
    private array $serverExists = [];

    /**
     * Snapshot server state before each test.
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

        foreach (self::KEYS as $key) {
            $this->serverExists[$key] = array_key_exists($key, $_SERVER);
            $this->serverBackup[$key] = $this->serverExists[$key] ? $_SERVER[$key] : null;
        }
    }

    /**
     * Restore server state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            if ($this->serverExists[$key]) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }
        }
    }

    /**
     * Base host and its www variant must be allowed, evil must not.
     *
     * @return void
     */
    public function testAllowlistAcceptsBaseAndWwwButRejectsEvil(): void
    {
        $baseUrl = 'http://localhost:8080/';

        $this->assertTrue(PreProcessor::isAllowedHost('localhost:8080', $baseUrl));
        $this->assertTrue(PreProcessor::isAllowedHost('localhost', $baseUrl));
        $this->assertTrue(PreProcessor::isAllowedHost('www.localhost:8080', $baseUrl));
        $this->assertFalse(PreProcessor::isAllowedHost('evil.com', $baseUrl));
        $this->assertFalse(PreProcessor::isAllowedHost("evil.com\r\nSet-Cookie: x=1", $baseUrl));
    }

    /**
     * Bare base host must allow both bare and www forms.
     *
     * @return void
     */
    public function testAllowlistForBareDomain(): void
    {
        $baseUrl = 'https://example.com/';

        $this->assertTrue(PreProcessor::isAllowedHost('example.com', $baseUrl));
        $this->assertTrue(PreProcessor::isAllowedHost('www.example.com', $baseUrl));
        $this->assertFalse(PreProcessor::isAllowedHost('evil.com', $baseUrl));
        $this->assertFalse(PreProcessor::isAllowedHost('example.com.evil.com', $baseUrl));
    }

    /**
     * URI encoding must strip CR/LF and encode spaces.
     *
     * @return void
     */
    public function testEncodeUriStripsCrlfAndEncodes(): void
    {
        $this->assertSame('/', PreProcessor::encodeUri("//evil.com"));
        $this->assertSame('/foo/bar?a=1', PreProcessor::encodeUri("/foo/bar?a=1"));
        $this->assertSame('/foo%20bar', PreProcessor::encodeUri('/foo bar'));
        $this->assertStringNotContainsString("\r", PreProcessor::encodeUri("/x\r\nSet-Cookie: 1"));
        $this->assertStringNotContainsString("\n", PreProcessor::encodeUri("/x\r\nSet-Cookie: 1"));
    }

    /**
     * Non-www redirect builder must use the encoded URI.
     *
     * @return void
     */
    public function testBuildNonWwwRedirectUsesEncodedUri(): void
    {
        $this->assertSame(
            'http://example.com/foo%20bar',
            PreProcessor::buildNonWwwRedirect('www.example.com', '/foo bar', 'http')
        );
        $this->assertNull(PreProcessor::buildNonWwwRedirect('example.com', '/x', 'http'));
    }

    /**
     * Spoofed hosts must never leak into fullUrl.
     *
     * @return void
     */
    public function testFullUrlDropsSpoofedHost(): void
    {
        $_SERVER['HTTP_HOST'] = 'evil.com';
        $_SERVER['REQUEST_URI'] = '/path?q=1';
        $_SERVER['HTTPS'] = 'off';

        $url = Request::fullUrl();

        $this->assertStringNotContainsString('evil.com', $url);
        $this->assertStringContainsString('/path?q=1', $url);
    }
}
