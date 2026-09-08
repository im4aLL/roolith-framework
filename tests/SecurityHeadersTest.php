<?php
namespace Tests;

use App\Core\System;
use PHPUnit\Framework\TestCase;

/**
 * Covers baseline security headers.
 */
class SecurityHeadersTest extends TestCase
{
    /**
     * Baseline map must contain all five headers with safe values.
     *
     * @return void
     */
    public function testSecurityHeadersContainBaseline(): void
    {
        $headers = System::securityHeaders();

        $this->assertSame("default-src 'self'", $headers['Content-Security-Policy']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        $this->assertSame('max-age=31536000; includeSubDomains', $headers['Strict-Transport-Security']);
    }
}
