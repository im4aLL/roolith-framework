<?php

use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\AdminPageController;
use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use App\Middlewares\AuthMiddleware;
use Roolith\Configuration\Config;
use App\Controllers\Admin\AdminModuleController;

$router = RouterFactory::getInstance();

try {
    $router->setBaseUrl(Config::get('baseUrl'));
    $router->setViewDir(APP_VIEW_ROOT);
} catch (\Roolith\Configuration\Exception\InvalidArgumentException $e) {
    echo $e->getMessage();
}

$router->get('/', function() {
    return 'Welcome to Roolith Framework!';
});

$router->get('/example', WelcomeController::class . '@index');
$router->get('/form', WelcomeController::class . '@form')->name('welcome.form');
$router->post('/form', WelcomeController::class . '@formSubmit');

$router->group(['middleware' => AuthMiddleware::class, 'urlPrefix' => '/admin', 'namePrefix' => 'admin.'], function () use ($router) {
    $router->get('/', AdminController::class . '@index')->name('home');
    $router->crud('/pages', AdminPageController::class);
    $router->crud('/modules', AdminModuleController::class);
});

return $router;
