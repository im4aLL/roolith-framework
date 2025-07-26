<?php

use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminCategoryController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\AdminMessageController;
use App\Controllers\Admin\AdminMiscController;
use App\Controllers\Admin\AdminModuleController;
use App\Controllers\Admin\AdminModuleSettingController;
use App\Controllers\Admin\AdminPageController;
use App\Controllers\Admin\AdminSettingController;
use App\Controllers\Content\BlogController;
use App\Controllers\Content\CategoryController;
use App\Controllers\Content\PageController;
use App\Core\RouterFactory;
use App\Middlewares\Admin\AdminAuthMiddleware;

$router = RouterFactory::getInstance();

/**
 * Admin routes
 */
$router->get('/admin/login', AdminAuthController::class . '@login')->name('admin.auth.login');
$router->post('/admin/login', AdminAuthController::class . '@verifyCredential')->name('admin.auth.verifyCredential');
$router->post('/message', AdminMessageController::class . '@sendMessage')->name('admin.messages.sendMessage');

$router->group(['middleware' => AdminAuthMiddleware::class, 'urlPrefix' => '/admin', 'namePrefix' => 'admin.'], function () use ($router) {
    /**
     * Dashboard
     */
    $router->get('/', AdminController::class . '@index')->name('home');

    /**
     * Page
     */
    $router->crud('/pages', AdminPageController::class);
    $router->post('/pages/file/upload', AdminPageController::class . '@fileUpload')->name('pages.fileUpload');

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

    /**
     * Message
     */
    $router->crud('/messages', AdminMessageController::class);
});

/**
 * CMS routes
 */
$router->get('/categories', CategoryController::class .  '@index')->name('categories.index');
$router->get('/categories/{slug}', CategoryController::class .  '@show')->name('categories.show');
$router->get('/blog', BlogController::class . '@index')->name('blog.index');
$router->get('/blog/{slug}', BlogController::class . '@show')->name('blog.show');
$router->get('/{slug}', PageController::class . '@show')->name('page.show');
