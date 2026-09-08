<?php

use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use Roolith\Configuration\Config;

$router = RouterFactory::getInstance();

try {
    $router->setBaseUrl(Config::get("baseUrl"));
    $router->setViewDir(APP_VIEW_ROOT);
} catch (\Roolith\Configuration\Exception\InvalidArgumentException $e) {
    // Fail-closed: never echo config details with HTTP 200. Rethrow so the
    // front-controller ErrorHandler logs the full trace and returns a
    // generic 500 in prod (details only in dev).
    throw new \App\Core\Exceptions\Exception("Router bootstrap failed: " . $e->getMessage(), 0, $e);
}

/**
 * Demo routes
 */
$router->get("/", function () {
    return "Welcome to Roolith Framework!";
});

$router->get("/example", WelcomeController::class . "@index");
$router->get("/form", WelcomeController::class . "@form")->name("welcome.form");
$router->post("/form", WelcomeController::class . "@formSubmit")->middleware(CsrfMiddleware::class);

/**
 * Auth example (009-A9): deny-by-default middleware with new process(request, next).
 *
 * AuthMiddleware checks $_SESSION['user_id']; guests get a 302 redirect to
 * /login, authed users flow to $next (controller). The vendor router runs
 * native next-style middleware (Roolith\Route\Interfaces\
 * NextMiddlewareInterface) with onion post-processing, so attach entries
 * directly as instances or class-strings.
 *
 * Attach per route with ->middleware(new X()) or ->middleware(X::class).
 * Group example:
 *   $router->group(['middleware' => [new AuthMiddleware()]], function ($r) {
 *       $r->get("/dashboard", fn () => "Dashboard");
 *   });
 */
$router->get("/login", function (): string {
    return "Login page - POST credentials here.";
})->name("login");

$router->get("/dashboard", function (): string {
    return "Dashboard";
})->middleware(AuthMiddleware::class);

/**
 * CSRF example (008-A8): state-changing routes require the per-session token.
 *
 * Forms must include csrf_field() (hidden _csrf input); fetch clients may
 * send X-CSRF-TOKEN instead. GET stays open, POST without a valid token is
 * blocked with 403 and the controller never runs.
 */
$router->post("/form-secure", function (): string {
    return "Secure form submitted.";
})->middleware(CsrfMiddleware::class);

/**
 * CMS related routes (037-C4 CMS-only).
 *
 * Mounted only when the explicit APP_ENABLE_CMS env flag is on and the CMS
 * release asset installed app/Http/cms-routes.php (see
 * docs/cms-installer.md). Missing file is skipped so core boots without it.
 * Gate parity with System.php: defined plus flag plus is_file plus
 * is_readable so CMS routes never load in core-only mode or from an
 * unreadable path.
 *
 * @var string $cmsRoutesPath Absolute path to the optional CMS routes file.
 */
$cmsRoutesPath = (defined('APP_ROOT') ? (string) APP_ROOT : dirname(__DIR__, 2)) . "/app/Http/cms-routes.php";

if (defined('APP_ENABLE_CMS') && APP_ENABLE_CMS && is_file($cmsRoutesPath) && is_readable($cmsRoutesPath)) {
    require_once $cmsRoutesPath;
}

return $router;
