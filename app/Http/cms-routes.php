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
use App\Controllers\Admin\AdminAnalyticsController;

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
    $router->match(['GET', 'POST'], '/file-manager', AdminMiscController::class . '@fileManager', 'file.manager');

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
    $router->post('/site-settings/feature/{param}', AdminSettingController::class . '@toggleFeature')->name('siteSettings.toogle');
    $router->put('/site-settings/{param}', AdminSettingController::class . '@update')->name('siteSettings.update');
    $router->delete('/site-settings/{param}', AdminSettingController::class . '@destroy')->name('siteSettings.destroy');

    /**
     * Message
     */
    $router->crud('/messages', AdminMessageController::class);

    /**
     * UI states
     */
    $router->post('/ui-states', AdminMiscController::class . '@storeUiStates')->name('ui.states');

    /**
     * Analytics
     */
    $router->get('/analytics', AdminAnalyticsController::class . '@index')->name('analytics.index');
    $router->get('/analytics/overview', AdminAnalyticsController::class . '@overview')->name('analytics.overview');
    $router->get('/analytics/overview/lifetime', AdminAnalyticsController::class . '@lifetimeOverview')->name('analytics.overview.lifetime');
    $router->get('/analytics/top-pages', AdminAnalyticsController::class . '@topPages')->name('analytics.topPages');
    $router->get('/analytics/top-sources', AdminAnalyticsController::class . '@topSources')->name('analytics.topSources');
    $router->get('/analytics/location-stats', AdminAnalyticsController::class . '@locationStats')->name('analytics.locationStats');
    $router->get('/analytics/device-stats', AdminAnalyticsController::class . '@deviceStats')->name('analytics.deviceStats');
    $router->get('/analytics/daily-trends', AdminAnalyticsController::class . '@dailyTrends')->name('analytics.dailyTrends');
    $router->get('/analytics/hourly-trends', AdminAnalyticsController::class . '@hourlyTrends')->name('analytics.hourlyTrends');
    $router->get('/analytics/period', AdminAnalyticsController::class . '@periodName')->name('analytics.periodName');
    $router->get('/analytics/set-period', AdminAnalyticsController::class . '@setPeriod')->name('analytics.setPeriod');
});

/**
 * CMS routes
 */
$router->get('/categories', CategoryController::class .  '@index')->name('categories.index');
$router->get('/categories/{slug}', CategoryController::class .  '@show')->name('categories.show');
$router->get('/blog', BlogController::class . '@index')->name('blog.index');
$router->get('/blog/{slug}', BlogController::class . '@show')->name('blog.show');
$router->get('/{slug}', PageController::class . '@show')->name('page.show');
