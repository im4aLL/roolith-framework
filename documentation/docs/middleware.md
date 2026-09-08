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

`App\Middlewares\AuthMiddleware` redirects guests to `/login` and lets logged-in users through. It reads the session key (default `user_id`) and only passes real positive ids: an `int` greater than zero, or a trimmed non-empty string that is not zero. `0`, `"0"` (plus numeric zero variants like `"00"` and `"0.0"`), `""`, `null`, `false`, and `[]` all deny. The login redirect target is allowlisted internally, so a misconfigured path can never become an open redirect.

```php
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware(AuthMiddleware::class);
```

Pass a custom login path or session key by constructing it directly:

```php
use App\Middlewares\AuthMiddleware;

$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(new AuthMiddleware('/admin-login', 'admin_id'));
```

Share one guard across many routes with a group, as shown in Attaching to routes above (see [Routing](/routing)).

## Protecting forms with CsrfMiddleware

`App\Middlewares\CsrfMiddleware` blocks forged posts. Reading routes (`GET`, `HEAD`, `OPTIONS`) pass through; `POST`, `PUT`, `PATCH`, and `DELETE` need a valid token or the request stops with a `403` before your controller runs.

The token lifecycle: `Csrf::token()` generates one 64-char hex token per session and reuses it until rotated. Forms send it via the hidden `_csrf` field, fetch or XHR clients send the `X-CSRF-TOKEN` (or `X-XSRF-TOKEN`) header instead. Validation compares with `hash_equals`, so missing or mismatched tokens fail closed. Rotate the token on login with `Csrf::rotate()`.

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

```js
fetch('/form', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token },
});
```

## Slowing brute force with rate limiting

Throttle repeated attempts such as logins with `SessionRateLimiter`, the session-backed driver behind the `RateLimiterInterface` contract (`hit`, `tooManyAttempts`, `clear`). Check before verifying credentials, record failures with `hit()`, and clear on success. Use `count()` to show remaining attempts.

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

Limits live in the session, so they are not distributed: a visitor who clears cookies gets a fresh bucket, and buckets do not carry across multiple servers unless PHP sessions are shared. Program to `RateLimiterInterface` when you want to swap in a shared backend later without touching call sites.
