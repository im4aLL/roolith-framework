<?php

use App\Controllers\Admin\AdminCategoryController;
use App\Controllers\Admin\AdminMiscController;
use App\Controllers\Admin\AdminModuleSettingController;
use App\Controllers\Admin\AdminSettingController;
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
    /**
     * Dashboard
     */
    $router->get('/', AdminController::class . '@index')->name('home');

    /**
     * Page
     */
    $router->crud('/pages', AdminPageController::class);

    /**
     * Category
     */
    $router->crud('/categories', AdminCategoryController::class);

    /**
     * Module
     */
    $router->crud('/modules', AdminModuleController::class);
    $router->delete('/module/file', AdminModuleController::class . '@deleteFile')->name('module.file.delete');

    /**
     * Module settings
     */
    $router->crud('/module-settings', AdminModuleSettingController::class);

    /**
     * File manager
     */
    $router->match(['GET', 'POST'], '/file-manager', AdminMiscController::class . '@fileManager')->name('file.manager');

    /**
     * Auth
     */
    $router->get('/logout', AdminAuthController::class . '@logout')->name('auth.logout');
    $router->get('/change-password', AdminAuthController::class . '@changePassword')->name('auth.changePassword');
    $router->post('/change-password', AdminAuthController::class . '@updatePassword')->name('auth.updatePassword');

    /**
     * Setting
     */
    $router->get('/site-settings', AdminSettingController::class . '@index')->name('siteSettings');
    $router->post('/site-settings', AdminSettingController::class . '@store')->name('siteSettings.store');
    $router->put('/site-settings/{param}', AdminSettingController::class . '@update')->name('siteSettings.update');
    $router->delete('/site-settings/{param}', AdminSettingController::class . '@destroy')->name('siteSettings.destroy');
});

//p($router->getRouteList(), true);

return $router;
