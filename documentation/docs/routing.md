# Routing

Routes live in `app/Http/routes.php`. Each route maps a method plus URL to a closure or a controller action.

```php
use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

$router = RouterFactory::getInstance();
$router->setBaseUrl(Config::get("baseUrl"));
$router->setViewDir(APP_VIEW_ROOT);
```

## Basic routes

```php
$router->get("/", function () {
    return "Welcome to Roolith Framework!";
});

$router->post("/form", [WelcomeController::class, "formSubmit"]);
```

Available verbs are `get`, `post`, `put`, `patch`, `delete`, and `options`. `any()` registers all six at once, and `match()` registers a chosen subset.

```php
$router->any('/ping', function () { return 'pong'; });
$router->match(['GET', 'POST'], '/user', function () { return 'GET POST content.'; });
```

Paths are stored with a leading `/`, so `'user'` and `'/user'` are the same route. Trailing slashes are literal, so prefer registering without one. A verb also accepts an array of paths to register the same handler twice.

## Controller routes

Always use the callable form so typos are caught by `php roolith route:list`.

```php
$router->get("/example", [WelcomeController::class, "index"]);
```

## Route params

Segments in `{braces}` are passed to the handler in order. Optional segments need a default value.

```php
$router->get('user/{id}', function ($id) {
    return 'User id ' . $id;
});

$router->get('/user/{userId}/edit/{section}', function ($userId, $section) {
    return 'userId: ' . $userId . ' section: ' . $section;
});

$router->get('name/{name?}', function ($name = 'Guest') {
    return "Your name is - $name";
});
```

Inside middleware, read them with `$request->getParam('id')` (`false` when absent) and query strings with `$request->getUrlParam('q')` (`null` when absent).

## Named routes

Name a route to reference it in templates and code. Placeholders are URL-encoded, and a missing name returns the base URL unchanged.

```php
$router->get("/form", [WelcomeController::class, "form"])->name("welcome.form");
$router->getUrlByName('welcome.form');
```

```php
<form action="<?= route('welcome.form') ?>" method="post">
```

## What handlers return

Return a string for HTML. In controllers, return `$this->view()` for pages or `$this->json()` for JSON (see [Controllers](/controllers)). See [Response](/response) for the full return-type guide and [Error Handling](/error-handling) for 404 plus 405 behavior.

```php
$router->get('/users', function () {
    return 'plain HTML string';
});
```

Unknown paths respond with `404`, and a path that exists for another method responds with `405 Method Not Allowed` plus an `Allow` header.

## Middleware

Attach middleware per route or pass an array for several. See [Middleware](/middleware) for the full list and how to write your own.

```php
$router->post("/form", [WelcomeController::class, "formSubmit"])->middleware(CsrfMiddleware::class);
$router->get("/dashboard", function (): string { return "Dashboard"; })->middleware(AuthMiddleware::class);
```

## Groups

Group routes that share a URL prefix, name prefix, or middleware. Groups nest, with inner prefixes appended and middleware stacked outer-first.

```php
$router->group(['middleware' => AuthMiddleware::class, 'urlPrefix' => 'user/{userId}', 'namePrefix' => 'user.'], function () use ($router) {
    $router->get('profile', function ($userId) {
        return "profile route: User id: $userId";
    })->name('profile');
});
```

## CRUD routes

One call generates the standard index, create, show, edit, store, update, and destroy routes with `crud.*` names.

```php
$router->crud('/crud', WelcomeController::class);
```

## Redirects

```php
$router->redirect('/old', '/new');
$router->redirect('/old', '/new', 302); // default is 301
```

## Checking your routes

List and lint all routes any time. Invalid handlers exit with code `1`, so this works as a CI gate. See [CLI](/cli).

```bash
php roolith route:list
```
