<?php
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

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

$router->get('/example', \App\Controllers\WelcomeController::class . '@index');
$router->get('/form', \App\Controllers\WelcomeController::class . '@form')->name('welcome.form');
$router->post('/form', \App\Controllers\WelcomeController::class . '@formSubmit');

return $router;
