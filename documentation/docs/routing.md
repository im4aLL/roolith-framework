# Routing

Routes are defined in `app/Http/routes.php`.
The framework uses [roolith/router](https://github.com/im4aLL/roolith-router) under the hood.
The router instance is pre-configured with the base URL and the view directory.

```php
use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

$router = RouterFactory::getInstance();

$router->setBaseUrl(Config::get("baseUrl"));
$router->setViewDir(APP_VIEW_ROOT);
```

## Basic Routes

```php
$router->get("/", function () {
    return "Welcome to Roolith Framework!";
});

$router->post("/form", WelcomeController::class . "@formSubmit");
$router->put('/test', function() { return 'put content'; });
$router->patch('/test', function() { return 'patch content'; });
$router->delete('/test', function() { return 'delete content'; });
$router->options('/test', function() { return 'options content'; });
```

The wildcard method `any` responds to all HTTP methods.

```php
$router->any('any', function() {
    return 'any content. Server request method: ' . $_SERVER['REQUEST_METHOD'];
});
```

## Controller Routes

Point a route to a controller method with the `Controller@method` syntax.

```php
$router->get("/example", WelcomeController::class . "@index");
```

## Named Routes

Give a route a name so you can reference it in templates and code.

```php
$router->get("/form", WelcomeController::class . "@form")->name("welcome.form");

// get url by name
$router->getUrlByName('welcome.form');
```

Print a route URL inside a template.

```php
<form action="<?= route('welcome.form') ?>" method="post">
```

## Route Params

```php
$router->get('user/{id}', function($id) {
    return 'User id ' . $id;
});

$router->get('/user/{userId}/edit/{another}', function($userId, $another) {
    return 'userId: ' . $userId . ' another: ' . $another;
});
```

Optional params fall back to a default value.

```php
$router->get('name/{name?}', function($name = 'Default name') {
    return "Your name is - $name";
});
```

## Multiple Routes at Once

```php
$router->get(['user', 'profile'], function() {
    return ['name' => 'John', 'age' => 45];
});
```

## Multiple Methods at Once

```php
$router->match(['GET', 'POST'], '/user', function() {
    return 'GET POST content.';
});
```

## Middleware

```php
$router->get('/admin/dashboard', function() {
    return 'Dashboard content';
})->middleware(\App\Middlewares\AuthMiddleware::class);
```

## Route Groups

Group routes with a shared middleware, URL prefix and name prefix.

```php
$router->group(['middleware' => \App\Middlewares\AuthMiddleware::class, 'urlPrefix' => 'user/{userId}', 'namePrefix' => 'user.'], function () use ($router) {
    $router->get('profile', function ($userId) {
        return "profile route: User id: $userId";
    })->name('profile');

    $router->get('action/{actionId}', function ($userId, $actionId) {
        return "action route: User id: $userId and action id $actionId";
    })->name('action');
});
```

## CRUD Routes

Generate a full set of REST-like routes in one call.

```php
$router->crud('/crud', WelcomeController::class);
```

The above is equivalent to:

```php
$router->get('/crud',              'Controller@index')->name('crud.index');
$router->get('/crud/create',       'Controller@create')->name('crud.create');
$router->get('/crud/{item}',       'Controller@show')->name('crud.show');
$router->get('/crud/{item}/edit',  'Controller@edit')->name('crud.edit');
$router->post('/crud',             'Controller@store')->name('crud.store');
$router->put('/crud/{item}',       'Controller@update')->name('crud.update');
$router->patch('/crud/{item}',     'Controller@update')->name('crud.update');
$router->delete('/crud/{item}',    'Controller@destroy')->name('crud.destroy');
```

With a closure instead of a controller, all routes point to the closure and use the same `crud.*` names.

## Redirects

```php
$router->redirect('/redirect', '/redirected');
$router->redirect('/redirect', '/redirected', 302);
```

## Route List

```php
$router->getRouteList();
```

## CMS Routes

CMS related routes live in `app/Http/cms-routes.php` and are only loaded when `APP_ENABLE_CMS` is `true`.

```php
if (APP_ENABLE_CMS) {
    require_once APP_ROOT . "/app/Http/cms-routes.php";
}
```
