<?php

use App\Controllers\Admin\AdminMiscController;
use App\Controllers\Admin\AdminModuleSettingController;
use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

use App\Controllers\Admin\AdminModuleController;
use App\Middlewares\Admin\AdminAuthMiddleware;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\AdminPageController;

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

/**
 * Admin routes
 */
$router->get('/admin/login', AdminAuthController::class . '@login')->name('admin.auth.login');
$router->post('/admin/login', AdminAuthController::class . '@verifyCredential')->name('admin.auth.verifyCredential');

$router->group(['middleware' => AdminAuthMiddleware::class, 'urlPrefix' => '/admin', 'namePrefix' => 'admin.'], function () use ($router) {
    $router->get('/', AdminController::class . '@index')->name('home');
    $router->crud('/pages', AdminPageController::class);
    $router->crud('/modules', AdminModuleController::class);
    $router->crud('/module-settings', AdminModuleSettingController::class);
    $router->match(['GET', 'POST'], '/file-manager', AdminMiscController::class . '@fileManager')->name('file.manager');

    $router->get('/logout', AdminAuthController::class . '@logout')->name('auth.logout');
});

//p($router->getRouteList(), true);

return $router;
