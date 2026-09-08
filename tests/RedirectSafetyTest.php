<?php
namespace Tests;

use App\Core\PreProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Covers safe redirect targets against open-redirect payloads.
 */
class RedirectSafetyTest extends TestCase
{
    /**
     * Base URL used for allowlist assertions.
     *
     * @var string
     */
    private const BASE_URL = 'http://localhost:8080/';

    /**
     * Ensure framework constants and helpers exist.
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
    }

    /**
     * Single-slash relative URLs must pass through.
     *
     * @return void
     */
    public function testRelativeUrlIsAllowed(): void
    {
        $this->assertSame('/dashboard', PreProcessor::resolveSafeRedirectTarget('/dashboard', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('/', self::BASE_URL));
        $this->assertSame('/foo?next=1', PreProcessor::resolveSafeRedirectTarget('/foo?next=1', self::BASE_URL));
    }

    /**
     * Evil absolute URLs must fall back to /.
     *
     * @return void
     */
    public function testEvilNextPayloadFallsBackToSlash(): void
    {
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('https://evil.com', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('https://evil.com/phish?next=1', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('http://evil.com', self::BASE_URL));
    }

    /**
     * Protocol-relative and non-http schemes must fall back to /.
     *
     * @return void
     */
    public function testProtocolRelativeAndOtherSchemesAreRejected(): void
    {
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('//evil.com/path', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('javascript:alert(1)', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('data:text/html,hi', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('/\\evil', self::BASE_URL));
    }

    /**
     * Allowlisted absolute URLs must pass through.
     *
     * @return void
     */
    public function testAllowlistedAbsoluteUrlIsAllowed(): void
    {
        $this->assertSame(
            'http://localhost:8080/dashboard',
            PreProcessor::resolveSafeRedirectTarget('http://localhost:8080/dashboard', self::BASE_URL)
        );
        $this->assertSame(
            'http://www.localhost:8080/dashboard',
            PreProcessor::resolveSafeRedirectTarget('http://www.localhost:8080/dashboard', self::BASE_URL)
        );
    }

    /**
     * CR/LF injection must be stripped and empty must fall back.
     *
     * @return void
     */
    public function testCrlfStrippedAndEmptyFallsBack(): void
    {
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget('', self::BASE_URL));
        $this->assertSame('/', PreProcessor::resolveSafeRedirectTarget("https://evil.com\r\nSet-Cookie: x=1", self::BASE_URL));

        $relative = PreProcessor::resolveSafeRedirectTarget("/ok\r\nSet-Cookie: x=1", self::BASE_URL);

        $this->assertStringNotContainsString("\r", $relative);
        $this->assertStringNotContainsString("\n", $relative);
    }

    /**
     * Both redirect helpers must reuse the allowlist resolver.
     *
     * The helpers exit, so this asserts delegation without triggering headers.
     *
     * @return void
     */
    public function testRedirectHelpersReuseAllowlistResolver(): void
    {
        $requestSource = (string) file_get_contents(APP_ROOT . '/app/Core/Request.php');
        $helpersSource = (string) file_get_contents(APP_ROOT . '/app/Utils/functions.php');

        $this->assertStringContainsString('resolveSafeRedirectTarget', $requestSource);
        $this->assertStringContainsString('resolveSafeRedirectTarget', $helpersSource);
    }

    /**
     * 404 view must escape the message to block XSS.
     *
     * @return void
     */
    public function test404ViewEscapesMessage(): void
    {
        $message = '<script>alert(1)</script>';
        $expected = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        ob_start();
        include APP_ROOT . '/views/404.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString($expected, $output);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
    }

    /**
     * Www builders must use prefix checks, not substring search.
     *
     * Hosts merely containing www. elsewhere must still redirect.
     *
     * @return void
     */
    public function testWwwBuildersUsePrefixCheck(): void
    {
        $this->assertSame(
            'http://www.example.com/x',
            PreProcessor::buildWwwRedirect('example.com', '/x', 'http')
        );
        $this->assertNull(PreProcessor::buildWwwRedirect('www.example.com', '/x', 'http'));
        $this->assertSame(
            'http://www.mywww.example.com/x',
            PreProcessor::buildWwwRedirect('mywww.example.com', '/x', 'http')
        );
        $this->assertNull(PreProcessor::buildNonWwwRedirect('example.com', '/x', 'http'));
        $this->assertSame(
            'http://example.com/x',
            PreProcessor::buildNonWwwRedirect('www.example.com', '/x', 'http')
        );
    }

    /**
     * System must handle both FORCE_NON_WWW modes.
     *
     * forceNonWww=1 strips www, =0 adds www; the else branch keeps the
     * canonical redirect alive instead of disabling it.
     *
     * @return void
     */
    public function testSystemHandlesBothWwwModes(): void
    {
        $source = (string) file_get_contents(APP_ROOT . '/app/Core/System.php');

        $this->assertStringContainsString('PreProcessor::forceNonWww', $source);
        $this->assertStringContainsString('PreProcessor::forceWww', $source);
    }
}
