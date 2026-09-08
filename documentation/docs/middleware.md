# Middleware

Middleware runs a check before a route handler. Either it lets the request through or it stops it with a response, in which case the controller never runs.

## Creating one

```bash
php roolith generate middleware AuthMiddleware
```

```php
<?php
namespace App\Middlewares;

use Roolith\Route\Interfaces\NextMiddlewareInterface;
use Roolith\Route\Request as RouterRequest;

class AuthMiddleware implements NextMiddlewareInterface
{
    public function process(RouterRequest $request, callable $next): mixed
    {
        return $next($request); // let it through
    }
}
```

To block, return a response instead of calling `$next()`. Middleware classes live in `app/Middlewares`.

## Attaching to routes

```php
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware(AuthMiddleware::class);
```

Stack several with repeated calls or an array. They run in order and the first one to return a response wins.

```php
$router->get('/admin', fn () => 'Dashboard')->middleware([AuthMiddleware::class, CsrfMiddleware::class]);
```

Share one across many routes with a group (see [Routing](/routing)).

```php
$router->group(['middleware' => AuthMiddleware::class, 'urlPrefix' => 'admin'], function () use ($router) {
    $router->get('dashboard', fn () => 'Dashboard content');
    $router->get('users', fn () => 'User list');
});
```

## Protecting pages with AuthMiddleware

`App\Middlewares\AuthMiddleware` redirects guests to `/login` and lets logged-in users through. Pass a custom path or session key when constructing it directly.

```php
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware(AuthMiddleware::class);
```

## Protecting forms with CsrfMiddleware

`App\Middlewares\CsrfMiddleware` blocks forged posts. Reading routes (`GET`, `HEAD`, `OPTIONS`) pass through; `POST`, `PUT`, `PATCH`, and `DELETE` need a valid token or the request stops with a `403` before your controller runs.

```php
$router->post('/form', [FormController::class, 'submit'])->middleware(CsrfMiddleware::class);
```

Every protected form needs the hidden token field:

```html
<form method="POST" action="/form">
    <?= csrf_field() ?>
    <button type="submit">Submit</button>
</form>
```

Fetch or XHR clients can send the `X-CSRF-TOKEN` header instead.

## Slowing brute force with rate limiting

Throttle repeated attempts such as logins with `SessionRateLimiter`. Check before verifying credentials and clear on success.

```php
use App\Core\SessionRateLimiter;

$limiter = new SessionRateLimiter('login:' . $ip, 5, 60);

if ($limiter->tooManyAttempts()) {
    return 'Too many attempts, try again later.';
}

if ($loginOk) {
    $limiter->clear();
} else {
    $limiter->hit();
}
```
