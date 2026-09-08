<?php

use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
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
$router->post("/form", WelcomeController::class . "@formSubmit");

/**
 * CMS related routes
 */
if (APP_ENABLE_CMS) {
    require_once APP_ROOT . "/app/Http/cms-routes.php";
}

return $router;
