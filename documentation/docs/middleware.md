# Middleware

Middleware lets you run checks before a route is executed.
A middleware receives the [request](/request) and the response and returns a boolean.
When it returns `false`, the router rejects the request with an "Invalid request" response and the route is never executed. The status defaults to `403 Forbidden` and can be customized per middleware with the `$status_code` property.

## Creating a Middleware

Every middleware must extend `Roolith\Route\Middleware` and implement `process()`.
Generate a new one with the [generator](/generator).

```bash
php roolith generate middleware AuthMiddleware
```

```php
<?php
namespace App\Middlewares;

use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class AuthMiddleware extends Middleware
{
    public function process(Request $request, Response $response): bool
    {
        return true;
    }
}
```

The `process()` method receives the current `Request` and `Response`, so you can inspect headers, cookies, session and URL params before deciding whether to let the request through. It must stay `public` and return `bool`.

Set a custom rejection status with `$status_code`:

```php
use Roolith\Route\HttpConstants\HttpResponseCode;

class AuthMiddleware extends Middleware
{
    public int $status_code = HttpResponseCode::UNAUTHORIZED;

    public function process(Request $request, Response $response): bool
    {
        return false;
    }
}
```

## Checking Authentication

A typical authentication middleware checks whether a session is active before the route runs.

```php
<?php
namespace App\Middlewares;

use App\Core\Storage;
use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class AuthMiddleware extends Middleware
{
    public function process(Request $request, Response $response): bool
    {
        return Storage::hasSession(AUTH_STORAGE_NAME);
    }
}
```

## Checking Roles

You can combine checks inside a single middleware or by stacking middlewares.
The example below lets the request through only for authenticated users whose role is `manager` or `admin`.

```php
<?php
namespace App\Middlewares;

use App\Misc\AuthHelper;
use App\Models\User;
use Roolith\Route\Middleware;
use Roolith\Route\Request;
use Roolith\Route\Response;

class RoleMiddleware extends Middleware
{
    public function process(Request $request, Response $response): bool
    {
        if (!AuthHelper::isAuthenticated()) {
            return false;
        }

        $currentUser = User::current();

        return $currentUser->role == 'manager' || $currentUser->role == 'admin';
    }
}
```

## Using Middleware on a Route

Attach a middleware to a single route with the `middleware()` method.

```php
$router->get('/admin/dashboard', function () {
    return 'Dashboard content';
})->middleware(\App\Middlewares\RoleMiddleware::class);
```

Stack multiple middlewares with repeated calls or an array. They run in order and the first one to return `false` stops the request.

```php
$router->get('/admin/dashboard', function () {
    return 'Dashboard content';
})->middleware(\App\Middlewares\AuthMiddleware::class)->middleware(\App\Middlewares\RoleMiddleware::class);

$router->get('/admin/users', function () {
    return 'User list';
})->middleware([\App\Middlewares\AuthMiddleware::class, \App\Middlewares\RoleMiddleware::class]);
```

Already-instantiated entries also work and string entries resolve via the DI container with plain-instantiation fallback. An unknown or invalid entry responds with 500, and a throwing `process()` is logged and responds with a generic 500.

## Using Middleware on a Route Group

Share a middleware across many routes with a route group.
Combined with a URL prefix, this protects a whole section with one declaration.

```php
$router->group(['middleware' => \App\Middlewares\RoleMiddleware::class, 'urlPrefix' => 'admin'], function () use ($router) {
    $router->get('dashboard', function () {
        return 'Dashboard content';
    });

    $router->get('users', function () {
        return 'User list';
    });
});
```

## Notes

- Combine multiple checks either inside a single middleware class or by stacking middlewares. Group `middleware` runs outer-first, then route-level `->middleware()`.
- When `process()` returns `false`, the router stops and responds with the middleware's `$status_code` (`403` by default).
  Redirect to a login page instead of returning `false` if you want unauthenticated users to see a nicer flow.
- Middleware classes live in `app/Middlewares`.