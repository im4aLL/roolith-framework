<?php
namespace Tests;

use App\Core\Session;
use App\Core\Storage;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Covers hardened cookie params for session and Storage cookies.
 */
class CookieTest extends TestCase
{
    /**
     * Session params must be fail-closed by default.
     *
     * @return void
     */
    public function testSessionCookieParamsAreHardened(): void
    {
        $params = Session::cookieParams();

        $this->assertSame('/', $params['path']);
        $this->assertTrue($params['httponly']);
        $this->assertSame('Lax', $params['samesite']);
        $this->assertIsBool($params['secure']);
        $this->assertSame(0, $params['lifetime']);
    }

    /**
     * Overrides must win over defaults for the allowed keys only.
     *
     * @return void
     */
    public function testSessionCookieParamOverrides(): void
    {
        $params = Session::cookieParams(['samesite' => 'Strict', 'lifetime' => 3600, 'unknown' => 'x']);

        $this->assertSame('Strict', $params['samesite']);
        $this->assertSame(3600, $params['lifetime']);
        $this->assertArrayNotHasKey('unknown', $params);
    }

    /**
     * Storage cookie options must carry hardened flags with matching path.
     *
     * @return void
     */
    public function testStorageCookieOptionsAreHardened(): void
    {
        $expires = time() + 3600;
        $options = Storage::cookieOptions($expires);

        $this->assertSame($expires, $options['expires']);
        $this->assertSame('/', $options['path']);
        $this->assertTrue($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);
        $this->assertIsBool($options['secure']);
    }

    /**
     * Set and delete must queue headers and delete must clear the superglobal.
     *
     * @return void
     */
    public function testSetAndDeleteCookie(): void
    {
        $expiration = Carbon::now()->addHour();

        $this->assertTrue(Storage::setCookie('cookie_test', 'value', $expiration));

        $_COOKIE['cookie_test'] = 'value';

        $this->assertTrue(Storage::deleteCookie('cookie_test'));
        $this->assertArrayNotHasKey('cookie_test', $_COOKIE);
    }
}
