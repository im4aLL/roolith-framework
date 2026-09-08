<?php
namespace Tests;

use App\Core\Response;
use App\Middlewares\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Response as VendorResponse;

/**
 * Auth middleware: deny-by-default session guard with redirect for guests
 * and marker post-processing for authenticated users.
 */
class AuthMiddlewareTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $sessionBackup = null;

    /**
     * @var bool
     */
    private bool $hadSession = false;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->hadSession = isset($_SESSION);
        $this->sessionBackup = $this->hadSession ? $_SESSION : null;
        $_SESSION = [];
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->hadSession) {
            $_SESSION = $this->sessionBackup ?? [];
        } else {
            unset($_SESSION);
        }
    }

    /**
     * Auth middleware must deny guests and allow authed users.
     *
     * @return void
     */
    public function testAuthMiddlewareAllowAndDeny(): void
    {
        $middleware = new AuthMiddleware('/login', 'user_id');
        $request = new RouterRequest();

        unset($_SESSION['user_id']);

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): string {
            $called = true;

            return 'dashboard';
        });

        $this->assertFalse($called);
        $this->assertInstanceOf(VendorResponse::class, $result);
        $this->assertSame('/login', $result->getHeader('Location'));
        $this->assertSame(302, $result->getStatusCode());

        $_SESSION['user_id'] = '42';

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): Response {
            $called = true;

            return Response::text('dashboard');
        });

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('1', $result->header('X-Auth-Checked'));

        unset($_SESSION['user_id']);

        // Native vendor flow needs no adapter: guest denies with a redirect.
        $native = new AuthMiddleware('/login', 'user_id');

        $denied = $native->process($request, static fn (RouterRequest $req): string => 'blocked');

        $this->assertInstanceOf(VendorResponse::class, $denied);
        $this->assertSame(302, $denied->getStatusCode());

        $_SESSION['user_id'] = '7';

        $this->assertSame(
            'allowed',
            $native->process($request, static fn (RouterRequest $req): string => 'allowed')
        );

        unset($_SESSION['user_id']);
    }

    /**
     * Falsy ids never authenticate.
     *
     * @return void
     */
    public function testAuthDeniesFalsyIds(): void
    {
        $middleware = new AuthMiddleware('/login', 'user_id');
        $request = new RouterRequest();

        /** @var array<int, mixed> $falsy */
        $falsy = [0, '0', false, '', null, []];

        foreach ($falsy as $value) {
            $_SESSION['user_id'] = $value;

            $called = false;
            $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): string {
                $called = true;

                return 'dashboard';
            });

            $this->assertFalse($called, 'Falsy id must not authenticate: ' . var_export($value, true));
            $this->assertInstanceOf(VendorResponse::class, $result);
        }

        unset($_SESSION['user_id']);
    }

    /**
     * Real positive ids authenticate.
     *
     * @return void
     */
    public function testAuthAllowsPositiveIds(): void
    {
        $middleware = new AuthMiddleware('/login', 'user_id');
        $request = new RouterRequest();

        /** @var array<int, mixed> $valid */
        $valid = [1, 42, '42', '7', 'user-123'];

        foreach ($valid as $value) {
            $_SESSION['user_id'] = $value;

            $called = false;
            $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): Response {
                $called = true;

                return Response::text('dashboard');
            });

            $this->assertTrue($called, 'Positive id must authenticate: ' . var_export($value, true));
            $this->assertInstanceOf(Response::class, $result);
        }

        unset($_SESSION['user_id']);
    }
}
