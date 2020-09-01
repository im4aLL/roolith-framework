<?php
use Roolith\Configuration\Config;
use Roolith\Route\Router;

$router = new Router();

try {
    $router->setBaseUrl(Config::get('baseUrl'));
    $router->setViewDir(APP_VIEW_ROOT);
} catch (\Roolith\Configuration\Exception\InvalidArgumentException $e) {
    echo $e->getMessage();
}

$router->get('/', function() {
    return 'Welcome to Roolith Framework!';
});

$router->get('/example', \App\Controllers\WelcomeController::class . '@index');

return $router;
