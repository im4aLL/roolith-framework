# Middleware

Middleware lets you run checks before a route is executed.
A middleware receives the [request](/request) and the response and returns a boolean.
When it returns `false`, the router rejects the request with a `400 Bad Request` "Invalid request" response and the route is never executed.

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

The `process()` method receives the current `Request` and `Response`, so you can inspect headers, cookies, session and URL params before deciding whether to let the request through.

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

You can combine checks inside a single middleware.
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

- Only one middleware is supported per route or group.
  Combine multiple checks inside a single middleware class.
- When `process()` returns `false`, the router stops and responds with `400 Bad Request`.
  Redirect to a login page instead of returning `false` if you want unauthenticated users to see a nicer flow.
- Middleware classes live in `app/Middlewares`.