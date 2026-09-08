# Routing

Routes are defined in `app/Http/routes.php`.
The framework uses [roolith/router](https://github.com/im4aLL/roolith-router) under the hood.
The router instance is pre-configured with the base URL and the view directory. The framework runs it for you in `App\Core\System::processRequest()`, so route files only register routes and return the router.

```php
use App\Controllers\WelcomeController;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;

$router = RouterFactory::getInstance();

$router->setBaseUrl(Config::get("baseUrl"));
$router->setViewDir(APP_VIEW_ROOT);
```

`setBaseUrl()` sets the base used for stripping the request URL and for building redirect and named URLs, `getBaseUrl()` returns it. `setViewDir()` sets the directory used by `getViewHtmlByStatusCode()` for `<code>.php` error views. `setUseDI(false)` switches controller dispatch from PHP-DI (default, constructor dependencies injected) to plain `new Class()`.

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

The wildcard method `any` registers one route per known method (GET, POST, PUT, PATCH, DELETE, OPTIONS) and accepts a string or array path with an optional name as the third argument. `name()` and `middleware()` after `any()` apply to all 6 routes.

```php
$router->any('any', function() {
    return 'any content. Server request method: ' . $_SERVER['REQUEST_METHOD'];
});
```

All verbs accept a single path or an array of paths, so `post(['a', 'b'], $cb)` registers one route per path. Paths are stored with a leading `/`, so `'user'` and `'/user'` are the same route. Trailing slashes are literal (`'/a/'` only matches `'/a/'`), so prefer slash-less registration. Empty, null, or false paths and callbacks are skipped as no-ops.

## Controller Routes

Point a route to a controller method with the `Controller@method` syntax.
The array form `[Controller::class, 'method']` is identical and also works with `match()` and `any()`. Already-instantiated and static callables dispatch directly, non-static `[Class, 'method']` pairs normalize to `Class@method` and use DI.

```php
$router->get("/example", WelcomeController::class . "@index");
$router->get("/example", [WelcomeController::class, "index"]);
```

Route params are passed to the controller method the same way as closures. A missing class responds with 404, a missing method responds with 404, and an invalid handler (string without `@`, bad array shape) responds with 500 without leaking internals.

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

Placeholders are replaced with `urlencode()`d values, so `route('user.show', ['id' => 'a b/c'])` encodes spaces and slashes. A missing name returns the base URL unchanged. Inside a group the group `namePrefix` is prepended first, then `name()` appends, and `match()`/`any()` also accept the name inline as the last argument.

## Route Params

```php
$router->get('user/{id}', function($id) {
    return 'User id ' . $id;
});

$router->get('/user/{userId}/edit/{another}', function($userId, $another) {
    return 'userId: ' . $userId . ' another: ' . $another;
});
```

Matched values are passed to the handler as positional payload in segment order. Params accept dots and unicode slugs while static dots stay literal. Inside middleware, read them with `$request->getParam('id')` (returns `false` when absent) and query strings with `$request->getUrlParam('q')` (returns `null` when absent).

```php
use Roolith\Route\Request;
use Roolith\Route\Response;

class ShowMiddleware extends \Roolith\Route\Middleware
{
    public function process(Request $request, Response $response): bool
    {
        $id = $request->getParam('id');
        $q = $request->getUrlParam('q');

        return true;
    }
}
```

Optional params fall back to a default value.

```php
$router->get('name/{name?}', function($name = 'Default name') {
    return "Your name is - $name";
});
```

Optional segments expand to prefix routes: `'name/{name?}'` registers `/name` and `/name/{name}`, so give the handler a default value for the missing-param case. Multiple optionals expand as a prefix chain and a leading optional maps its empty prefix to `/`.

## Multiple Routes at Once

```php
$router->get(['user', 'profile'], function() {
    return ['name' => 'John', 'age' => 45];
});
```

Returning an array or object sends JSON (`Content-Type: application/json`), returning a string sends HTML. This applies to closures and controller returns alike.

## Multiple Methods at Once

```php
$router->match(['GET', 'POST'], '/user', function() {
    return 'GET POST content.';
});
```

Method names are case-insensitive and unknown verbs are ignored. `match()` also accepts an array path and an optional name as the fourth argument. `name()` and `middleware()` after `match()` apply to every registered route, not just the last one.

## Middleware

```php
$router->get('/admin/dashboard', function() {
    return 'Dashboard content';
})->middleware(\App\Middlewares\AuthMiddleware::class);
```

Chain or stack multiple middlewares with repeated calls or an array: `->middleware(A::class)->middleware(B::class)` runs `A` then `B`. A blocked request responds with `403 Forbidden` by default, or the middleware's own `$status_code`. See [Middleware](/middleware).

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

Groups can be nested. Inner `urlPrefix` and `namePrefix` append to the outer ones, `middleware` stacks outer-first, and the outer settings are restored afterwards. The group callback receives the router instance, so both `function ($router)` and `function () use ($router)` styles work. Placeholders in `urlPrefix` become leading handler args. Groups also apply to `redirect()` paths and names. `getGroupSettings()` returns the current settings array or `false` when empty, `setGroupSettings()` and `resetGroupSettings()` manage them manually.

## CRUD Routes

Generate a full set of REST-like routes in one call.

```php
$router->crud('/crud', WelcomeController::class);
```

The above is equivalent to:

```php
$router->get('/crud',                  'Controller@index')->name('crud.index');
$router->get('/crud/create',           'Controller@create')->name('crud.create');
$router->get('/crud/{param}',          'Controller@show')->name('crud.show');
$router->get('/crud/{param}/edit',     'Controller@edit')->name('crud.edit');
$router->post('/crud',                 'Controller@store')->name('crud.store');
$router->post('/crud/{param}',         'Controller@update')->name('crud._update');
$router->post('/crud/{param}/delete',  'Controller@destroy')->name('crud._destroy');
$router->put('/crud/{param}',          'Controller@update')->name('crud.update');
$router->patch('/crud/{param}',        'Controller@update')->name('crud.update');
$router->delete('/crud/{param}',       'Controller@destroy')->name('crud.destroy');
```

With a closure instead of a controller, all routes point to the closure and use the same `crud.*` names. `crud()` also accepts an array base such as `[Controller::class]`, which expands to `Controller@<crudMethod>` per route.

## Redirects

```php
$router->redirect('/redirect', '/redirected');
$router->redirect('/redirect', '/redirected', 302);
```

`redirect()` defaults to 301 and registers the source for all HTTP methods. Relative targets are joined to the base URL, absolute targets containing `://` are kept as-is. Redirect sources are literal paths, so register each concrete source with a separate call. Dispatch does not exit after sending the `Location` header.

## Route List

```php
$router->getRouteList();
```

Each entry has `method`, `path`, `execute` or `redirect`, `name`, `code` (redirects) and `middleware` (when set). Render it as a terminal-friendly ASCII table with `Method`, `Path`, `Name`, `Action`, and `Middleware` columns (`-` for missing values, `Closure` for closures, `Redirect to <target> (<code>)` for redirects, `No routes registered.` when empty).

```php
echo $router->formattedRouteList();
```

Use `$router->activeRoute()` (or the `getActiveRoute()` helper, which returns `[]` when nothing matches) to get the route matched for the current request, with `payload` for `{param}` values.

Unknown paths respond with 404. A path that exists for another method responds with 405 `Method Not Allowed` and an `Allow` header. Unknown verbs fall through to the same 405/404 handling.

## Responses

Route handlers use `Roolith\Route\Response` under the hood. `body()` echoes for backward compatibility and also stores the rendered string, which `renderBody()` returns without echoing and `getLastOutput()` recalls.

```php
use Roolith\Route\Response;

$response = new Response();
$response->body('hello');
$response->body(['hello' => 'world']);
$html = $response->renderBody('hello');
$last = $response->getLastOutput();
$response->setStatusCode(404);
$code = $response->getStatusCode();
$response->setHeaderJson();
$response->setHeaderHtml();
$response->setHeaderPlain();
$has = $response->hasHeaderContentType();
$response->errorResponse('Oops', 500);
$response->errorJson(['error' => 'Oops'], 500);
$response->redirect('http://example.com/target');
```

`errorResponse()` is HTML-only and defaults to 500, pass 403/404 explicitly where needed. `errorJson()` mirrors the same contract with a JSON body. `redirect()` strips CR/LF and does not exit.

## Error Views

```php
$router->setViewDir(APP_VIEW_ROOT);
$html = $router->getViewHtmlByStatusCode(404, 'fallback message');
```

When `view_dir` is set, `<code>.php` under that dir is included with exactly `$statusCode` and `$message` available, otherwise the fallback `$message` is returned as-is. Missing view files also return `$message`.

## CMS Routes

CMS related routes live in `app/Http/cms-routes.php` (installed via the CMS release asset, see [CMS installer](/cms-installer)) and are only loaded when the explicit `APP_ENABLE_CMS=1` env flag is on. Missing files are skipped so core boots without the asset.

```php
$cmsRoutesPath = APP_ROOT . "/app/Http/cms-routes.php";

if (defined('APP_ENABLE_CMS') && APP_ENABLE_CMS && is_file($cmsRoutesPath) && is_readable($cmsRoutesPath)) {
    require_once $cmsRoutesPath;
}
```
