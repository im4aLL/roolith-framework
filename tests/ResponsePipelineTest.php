<?php
namespace Tests;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\ErrorHandler;
use App\Core\PreProcessor;
use App\Core\Request;
use App\Core\Response;
use App\Core\RouterFactory;
use App\Core\RouterResponse;
use App\Core\Storage;
use App\Core\System;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;
use Roolith\Route\Interfaces\NextMiddlewareInterface;
use Roolith\Route\Request as RouterRequest;
use Roolith\Route\Router as VendorRouter;

/**
 * Response pipeline: immutable Response, redirects without exit, shutdown
 * fallback, route re-entry, native middleware order, JSON envelope, and the
 * controller view contract.
 */
class ResponsePipelineTest extends TestCase
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
        RouterFactory::resetForTests();
        System::resetShutdownForTests();
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
        RouterFactory::resetForTests();

        if (!headers_sent()) {
            http_response_code(200);
        }
    }

    /**
     * Response must be immutable with body, status, and headers.
     *
     * @return void
     */
    public function testResponseIsImmutableValue(): void
    {
        $base = new Response('hi', 200, ['X-A' => '1']);

        $this->assertSame('hi', $base->body());
        $this->assertSame(200, $base->status());
        $this->assertSame(['X-A' => '1'], $base->headers());
        $this->assertSame('1', $base->header('x-a'));

        $changed = $base->withBody('yo')->withStatus(201)->withHeader('X-B', '2');

        $this->assertSame('hi', $base->body());
        $this->assertSame(200, $base->status());
        $this->assertSame('yo', $changed->body());
        $this->assertSame(201, $changed->status());
        $this->assertSame('2', $changed->header('X-B'));
    }

    /**
     * Redirect helpers must return Response instead of exiting.
     *
     * @return void
     */
    public function testRedirectHelpersReturnResponse(): void
    {
        $global = redirect('/dashboard');

        $this->assertInstanceOf(Response::class, $global);
        $this->assertSame('/dashboard', $global->header('Location'));
        $this->assertSame(303, $global->status());

        $route = Request::redirect('/dashboard');

        $this->assertInstanceOf(Response::class, $route);
        $this->assertSame(302, $route->status());
        $this->assertSame('/dashboard', $route->header('Location'));

        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $_SERVER['REQUEST_URI'] = '/x';

        $pre = PreProcessor::forceNonWww(null, 'http://example.com/');

        $this->assertInstanceOf(Response::class, $pre);
        $this->assertSame(301, $pre->status());
        $this->assertStringContainsString('example.com', (string) $pre->header('Location'));

        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);

        $none = PreProcessor::forceNonWww(null, 'http://example.com/');

        $this->assertNull($none);
    }

    /**
     * Shutdown fallback must be registered and complete() idempotent.
     *
     * @return void
     */
    public function testShutdownFallbackRegisteredAndCompleteIdempotent(): void
    {
        $system = new System(new NullLogger());

        $this->assertTrue(System::isShutdownFallbackRegistered());

        $system->complete();
        $system->complete();

        $this->assertTrue(true);
    }

    /**
     * System::complete() must run after a redirect flow and clear temp.
     *
     * @return void
     */
    public function testSystemCompleteRunsAfterRedirect(): void
    {
        $system = new System(new NullLogger());

        Storage::setSession('temp_holder', 'x');
        Storage::temp('flash_key', 'flash_value');

        $this->assertSame('flash_value', Storage::getTemp('flash_key'));

        $response = redirect('/login');

        ob_start();
        $system->emit($response);
        ob_end_clean();

        $system->complete();

        $this->assertFalse(Storage::getTemp('flash_key'));
    }

    /**
     * RedirectException via ErrorHandler must emit and still complete.
     *
     * @return void
     */
    public function testRedirectExceptionEmitsAndCompletes(): void
    {
        $system = new System(new NullLogger());
        Storage::temp('flash_redirect', 'v');

        $exception = new \App\Core\Exceptions\RedirectException('/login', 302);

        ob_start();
        ErrorHandler::handle($system, $exception);
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
        $this->assertFalse(Storage::getTemp('flash_redirect'));
        $this->assertSame(302, $exception->getResponse()->status());
    }

    /**
     * Routes must re-enter in the same process without duplication.
     *
     * @return void
     */
    public function testRoutesReEntryInSameProcess(): void
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

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_HOST'] = 'localhost:8080';
            $_SERVER['REQUEST_URI'] = '/';

            $system = new System(new NullLogger());

            ob_start();
            try {
                $system->processRequest();
            } finally {
                ob_end_clean();
            }

            $first = count(RouterFactory::getInstance()->getRouteList());

            ob_start();
            try {
                $system->processRequest();
            } finally {
                ob_end_clean();
            }

            $second = count(RouterFactory::getInstance()->getRouteList());

            $this->assertGreaterThan(0, $first);
            $this->assertSame($first, $second);
        } finally {
            $instanceProp->setValue(null, $originalInstance);
            $configProp->setValue(null, $originalConfig);
        }
    }

    /**
     * Vendor native middleware must support next() and post-processing order.
     *
     * Drives a real vendor Router: two next-style middlewares around a
     * controller returning an App Response. Onion order plus the marker
     * header prove pre/post hooks run in the vendor flow with no adapter.
     *
     * @return void
     */
    public function testMiddlewareNextAndPostProcessing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';

        $order = [];

        /** @var mixed $seen */
        $seen = null;

        $first = new class ($order, $seen) implements NextMiddlewareInterface {
            /**
             * @param array<int, string> $order
             * @param mixed $seen
             */
            public function __construct(private array &$order, private mixed &$seen) {}

            /**
             * @param RouterRequest $request
             * @param callable $next
             * @return mixed
             */
            public function process(RouterRequest $request, callable $next): mixed
            {
                $this->order[] = 'first-before';
                $result = $next($request);
                $this->order[] = 'first-after';

                if ($result instanceof Response) {
                    $result = $result->withHeader('X-First', '1');
                }

                $this->seen = $result;

                return $result;
            }
        };

        $second = new class ($order) implements NextMiddlewareInterface {
            /**
             * @param array<int, string> $order
             */
            public function __construct(private array &$order) {}

            /**
             * @param RouterRequest $request
             * @param callable $next
             * @return mixed
             */
            public function process(RouterRequest $request, callable $next): mixed
            {
                $this->order[] = 'second-before';
                $result = $next($request);
                $this->order[] = 'second-after';

                return $result;
            }
        };

        $router = new VendorRouter([], new RouterResponse(), new RouterRequest());
        $router->setBaseUrl('http://localhost/');
        $router->get('/', static fn (): Response => Response::text('ok'))
            ->middleware($first)
            ->middleware($second);

        ob_start();
        $router->run();
        $output = (string) ob_get_clean();

        $this->assertSame(['first-before', 'second-before', 'second-after', 'first-after'], $order);
        $this->assertSame('ok', $output);
        $this->assertInstanceOf(Response::class, $seen);
        $this->assertSame('1', $seen->header('X-First'));
    }

    /**
     * JSON helper must set Content-Type application/json with status.
     *
     * @return void
     */
    public function testJsonHelperHasCorrectStatusAndHeaders(): void
    {
        $response = json(['a' => 1], 'success', 201, 'Created');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(201, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->header('Content-Type'));

        $decoded = json_decode($response->body(), true);

        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame(['a' => 1], $decoded['payload']);
        $this->assertSame('Created', $decoded['message']);

        $envelope = ApiResponseTransformer::errorResponse(null, 'Bad', 422);

        $this->assertSame(422, $envelope->status());
        $this->assertStringContainsString('application/json', (string) $envelope->header('Content-Type'));

        $routerResponse = new RouterResponse();
        $routerResponse->setStatusCode(200);

        ob_start();
        $routerResponse->body($response);
        ob_end_clean();

        $this->assertSame(201, $routerResponse->getStatusCode());
        $this->assertStringContainsString('"status":"success"', (string) $routerResponse->getLastOutput());
    }

    /**
     * Missing views must throw with context and never echo with 200.
     *
     * @return void
     */
    public function testMissingViewThrows(): void
    {
        $controller = new Controller();

        ob_start();

        try {
            $controller->view('__missing_view_test');
            $output = (string) ob_get_clean();
            $this->fail('Expected App exception for missing view.');
        } catch (\App\Core\Exceptions\Exception $e) {
            $output = (string) ob_get_clean();
            $this->assertStringContainsString('__missing_view_test', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }

        $this->assertSame('', $output);
    }

    /**
     * Unexpected throwables are wrapped with view context.
     *
     * @return void
     */
    public function testControllerWrapsUnexpectedThrowable(): void
    {
        $controller = new \App\Controllers\Controller();

        $stub = new class implements \Roolith\Template\Engine\Interfaces\ViewInterface {
            /**
             * @param string $folderName Base directory.
             * @return static
             */
            public function setViewFolder(string $folderName): static
            {
                return $this;
            }

            /**
             * @param string $filename View name.
             * @param array<string, mixed> $data View data.
             * @return string Never returns, always throws.
             */
            public function compile(string $filename, array $data = []): string
            {
                throw new \RuntimeException('boom-unexpected');
            }

            /**
             * @param string|false $baseUrl Base URL.
             * @return static
             */
            public function setBaseUrl(string|false $baseUrl): static
            {
                return $this;
            }

            /**
             * @return string|false Base URL.
             */
            public function getBaseUrl(): string|false
            {
                return false;
            }

            /**
             * @param string $filename View name.
             * @param array<string, mixed> $data View data.
             * @return static
             */
            public function inject(string $filename, array $data = []): static
            {
                return $this;
            }

            /**
             * @param string $urlSuffix URL suffix.
             * @return string URL.
             */
            public function url(string $urlSuffix): string
            {
                return $urlSuffix;
            }

            /**
             * @param mixed $value Value to escape.
             * @return string Escaped value.
             */
            public function e(mixed $value): string
            {
                return (string) $value;
            }

            /**
             * @param string $var Variable name.
             * @return string Escaped variable.
             */
            public function escape(string $var): string
            {
                return $var;
            }
        };

        $prop = new ReflectionProperty(\App\Controllers\Controller::class, 'templateEngine');
        $prop->setAccessible(true);
        $prop->setValue($controller, $stub);

        try {
            $controller->view('any-view');
            $this->fail('Expected App exception for unexpected throwable.');
        } catch (\App\Core\Exceptions\Exception $e) {
            $this->assertStringContainsString('any-view', $e->getMessage());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
        }
    }

    /**
     * renderBody preserves status plus headers.
     *
     * @return void
     */
    public function testRouterResponseRenderBodyPreservesHeaders(): void
    {
        $response = new RouterResponse();
        $app = Response::redirect('/login', 302);

        $body = $response->renderBody($app);

        $this->assertSame('', $body);
        $this->assertSame(302, $response->getStatusCode());
    }
}
