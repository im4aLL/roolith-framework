# Storage

Use `App\Core\Storage` for cookies, sessions, and one-request flash messages.

## Cookies

Save a cookie with an expiry date. Dates use [Carbon](https://carbon.nesbot.com/). Call this before any output is sent.

```php
use App\Core\Storage;
use Carbon\Carbon;

Storage::setCookie('theme', 'dark', Carbon::now()->addMonths(1));
```

Read a cookie with either `Storage` or `Request`. Both return `null` when missing.

```php
use App\Core\Request;
use App\Core\Storage;

$theme = Storage::getCookie('theme');
$theme = Request::cookie('theme');
```

Delete a cookie:

```php
use App\Core\Storage;

Storage::deleteCookie('theme');
```

Cookies are `HttpOnly` with `SameSite=Lax` by default, and `Secure` on `https`. Path and domain come from config or `.env` (`COOKIE_PATH`, `COOKIE_DOMAIN`, `COOKIE_SECURE`, `COOKIE_SAMESITE`).

## Sessions

Sessions start automatically, so just read and write. This is where login state usually lives (see [Extending a Model](/extending-a-model)).

```php
use App\Core\Storage;

Storage::setSession('user_email', 'a@b.com');
$email = Storage::getSession('user_email'); // value, or false when missing
$loggedIn = Storage::hasSession('user_email'); // bool
Storage::deleteSession('user_email'); // logout
```

Tip: `getSession()` returns `false` when the key is missing, so check with `hasSession()` when you need to tell missing apart from a stored falsy value.

After login, regenerate the session id to prevent fixation:

```php
use App\Core\Session;

Session::regenerate();
```

## Flash Data

Flash data lives for one request only. Set it, redirect, then read it on the next page. Useful for "Saved" notices after a form post.

```php
use App\Core\Storage;

// in POST handler:
Storage::temp('notice', 'Saved');
return Request::redirect('/dashboard');

// in dashboard controller or view:
echo Storage::getTemp('notice'); // value, or false when missing
```

## Rate limiting

Throttle logins and other repeated actions with `SessionRateLimiter`. Check first, record a failed attempt with `hit()`, and clear on success so logins do not stay throttled.

```php
use App\Core\SessionRateLimiter;

$limiter = new SessionRateLimiter('login:' . getIpAddress(), 5, 60);

if ($limiter->tooManyAttempts()) {
    return 'Too many attempts, try again later.';
}

if ($loginOk) {
    $limiter->clear();
} else {
    $limiter->hit();
}
```

Use `count()` when you want to show remaining attempts. Limits are stored in the session, so they reset if the visitor clears cookies and do not carry across multiple servers unless PHP sessions are shared.
```
