<?php
namespace Tests;

use App\Controllers\WelcomeController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\RouterFactory;
use App\Middlewares\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Response as VendorResponse;

/**
 * CSRF protection: per-session token, field helper, middleware gating for
 * state-changing routes, and the demo form route wiring.
 */
class CsrfTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $serverBackup = [];

    /**
     * @var array<string, mixed>
     */
    private array $postBackup = [];

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
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__));
        }

        if (!defined('APP_VIEW_ROOT')) {
            define('APP_VIEW_ROOT', APP_ROOT . '/views');
        }

        if (!defined('ROOLITH_CONFIG_ROOT')) {
            define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
        }

        if (!defined('APP_ENABLE_CMS')) {
            define('APP_ENABLE_CMS', false);
        }

        if (!function_exists('redirect')) {
            require_once APP_ROOT . '/app/Utils/functions.php';
        }

        $this->serverBackup = $_SERVER;
        $this->postBackup = $_POST;
        $this->hadSession = isset($_SESSION);
        $this->sessionBackup = $this->hadSession ? $_SESSION : null;
        $_SESSION = [];
        $_POST = [];

        Request::resetForTests();
        Csrf::resetForTests();
        RouterFactory::resetForTests();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;

        if ($this->hadSession) {
            $_SESSION = $this->sessionBackup ?? [];
        } else {
            unset($_SESSION);
        }

        Request::resetForTests();
        Csrf::resetForTests();
        RouterFactory::resetForTests();

        if (!headers_sent()) {
            http_response_code(200);
        }
    }

    /**
     * CSRF token must be stable per session with a view helper.
     *
     * @return void
     */
    public function testCsrfTokenStableAndField(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        $this->assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $first);
        $this->assertSame($first, $second);
        $this->assertTrue(Csrf::validate($first));
        $this->assertFalse(Csrf::validate('bad'));
        $this->assertFalse(Csrf::validate(null));

        $field = csrf_field();

        $this->assertStringContainsString('name="_csrf"', $field);
        $this->assertStringContainsString($first, $field);
        $this->assertSame($first, csrf_token());
    }

    /**
     * POST without CSRF token must be rejected and never reach next().
     *
     * @return void
     */
    public function testPostWithoutCsrfRejected(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new RouterRequest();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_XSRF_TOKEN']);

        // RouterRequest reads the method at construction, so mirror it.
        $request = new RouterRequest();

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): string {
            $called = true;

            return 'reached';
        });

        $this->assertFalse($called);
        $this->assertInstanceOf(VendorResponse::class, $result);
        $this->assertSame(403, $result->getStatusCode());

        $token = Csrf::token();
        $_POST['_csrf'] = $token;

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): \App\Core\Response {
            $called = true;

            return \App\Core\Response::text('ok');
        });

        $this->assertTrue($called);
        $this->assertInstanceOf(\App\Core\Response::class, $result);
        $this->assertSame('1', $result->header('X-CSRF-Validated'));

        unset($_SERVER['REQUEST_METHOD']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];

        $getRequest = new RouterRequest();
        $called = false;

        $result = $middleware->process($getRequest, static function (RouterRequest $req) use (&$called): string {
            $called = true;

            return 'open';
        });

        $this->assertTrue($called);
        $this->assertSame('open', $result);

        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Demo form controller must expose form plus formSubmit.
     *
     * @return void
     */
    public function testWelcomeControllerExposesFormMethods(): void
    {
        $this->assertTrue(method_exists(WelcomeController::class, 'form'));
        $this->assertTrue(method_exists(WelcomeController::class, 'formSubmit'));

        $ref = new ReflectionClass(WelcomeController::class);
        $form = $ref->getMethod('form');
        $submit = $ref->getMethod('formSubmit');

        $this->assertSame('string', (string) $form->getReturnType());
        $this->assertSame('mixed', (string) $submit->getReturnType());
    }

    /**
     * Demo form renders with a CSRF field and submit returns a message.
     *
     * @return void
     */
    public function testWelcomeFormRendersAndSubmits(): void
    {
        $controller = new WelcomeController();

        $html = $controller->form();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('name="_csrf"', $html);

        $result = $controller->formSubmit();

        $this->assertSame('Form submitted.', $result);
    }

    /**
     * Form routes exist and POST carries the CSRF middleware.
     *
     * @return void
     */
    public function testFormRoutesRegisteredWithCsrfMiddleware(): void
    {
        $routes = $this->loadRoutesForTests();

        $get = $this->findRoute($routes, '/form', 'GET');
        $post = $this->findRoute($routes, '/form', 'POST');

        $this->assertNotNull($get);
        $this->assertNotNull($post);
        $this->assertSame('App\Controllers\WelcomeController@form', $get['execute'] ?? null);
        $this->assertSame('App\Controllers\WelcomeController@formSubmit', $post['execute'] ?? null);

        $middlewares = $post['middleware'] ?? [];
        $this->assertIsArray($middlewares);
        $this->assertNotEmpty($middlewares);

        $foundCsrf = false;

        foreach ($middlewares as $middleware) {
            if ($middleware instanceof CsrfMiddleware) {
                $foundCsrf = true;
            }

            if (is_string($middleware) && is_a($middleware, CsrfMiddleware::class, true)) {
                $foundCsrf = true;
            }
        }

        $this->assertTrue($foundCsrf, 'POST /form must carry CsrfMiddleware.');
    }

    /**
     * Route-level: POST /form without a token is blocked with 403.
     *
     * The route carries the native CsrfMiddleware; without a token it
     * returns a vendor 403 Response and the controller never runs.
     *
     * @return void
     */
    public function testPostFormRouteBlocksWithoutToken(): void
    {
        $routes = $this->loadRoutesForTests();
        $post = $this->findRoute($routes, '/form', 'POST');

        $this->assertNotNull($post);

        /** @var array<int, mixed> $middlewares */
        $middlewares = $post['middleware'] ?? [];
        $entry = $middlewares[0] ?? null;

        $middleware = $entry instanceof CsrfMiddleware
            ? $entry
            : new CsrfMiddleware();

        if (is_string($entry)) {
            $this->assertTrue(is_a($entry, CsrfMiddleware::class, true));
        } else {
            $this->assertInstanceOf(CsrfMiddleware::class, $middleware);
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_XSRF_TOKEN']);

        $request = new RouterRequest();

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): string {
            $called = true;

            return 'reached';
        });

        $this->assertFalse($called);
        $this->assertInstanceOf(VendorResponse::class, $result);
        $this->assertSame(403, $result->getStatusCode());

        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Route-level: POST /form with a token passes the middleware.
     *
     * @return void
     */
    public function testPostFormRouteAllowsWithToken(): void
    {
        $routes = $this->loadRoutesForTests();
        $post = $this->findRoute($routes, '/form', 'POST');

        $this->assertNotNull($post);

        $middleware = new CsrfMiddleware();

        $token = Csrf::token();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf'] = $token;

        $request = new RouterRequest();

        $called = false;
        $result = $middleware->process($request, static function (RouterRequest $req) use (&$called): string {
            $called = true;

            return 'reached';
        });

        $this->assertTrue($called);
        $this->assertSame('reached', $result);

        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Load routes.php with an isolated Config baseUrl for assertions.
     *
     * @return array<int, array<string, mixed>> Route list.
     */
    private function loadRoutesForTests(): array
    {
        $ref = new ReflectionClass(\Roolith\Configuration\Config::class);
        $instanceProp = $ref->getProperty('instance');
        $instanceProp->setAccessible(true);
        $originalInstance = $instanceProp->getValue();
        $configProp = $ref->getProperty('configArray');
        $configProp->setAccessible(true);
        $originalConfig = $configProp->getValue();

        try {
            $dummy = $ref->newInstanceWithoutConstructor();
            $instanceProp->setValue(null, $dummy);
            $configProp->setValue(null, ['default' => ['baseUrl' => 'http://localhost:8080/']]);

            RouterFactory::reset();

            $router = require APP_ROOT . '/app/Http/routes.php';

            return $router->getRouteList();
        } finally {
            $instanceProp->setValue(null, $originalInstance);
            $configProp->setValue(null, $originalConfig);
            RouterFactory::reset();
        }
    }

    /**
     * Find a route by path plus method.
     *
     * @param array<int, array<string, mixed>> $routes Route list.
     * @param string $path Route path.
     * @param string $method HTTP method.
     * @return array<string, mixed>|null Matching route or null.
     */
    private function findRoute(array $routes, string $path, string $method): ?array
    {
        foreach ($routes as $route) {
            if (($route['path'] ?? null) === $path && ($route['method'] ?? null) === $method) {
                return $route;
            }
        }

        return null;
    }
}
