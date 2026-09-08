# Session

`App\Core\Session` owns session startup and cookie params. `App\Core\Storage` is the read and write helper on top of it. Everyday usage lives in [Storage](/storage); this page is the API reference.

## Starting the session

Call `Session::start()` before any output is sent. It is idempotent and never throws: it returns `true` when a session is active and `false` when startup fails.

```php
use App\Core\Session;

Session::start(); // bool
```

You rarely call it by hand. `Storage::setSession()`, `AuthMiddleware`, `Csrf`, and `SessionRateLimiter` all start the session for you. Call it directly only when you need the session active early (before headers go out) or when passing per-call overrides.

```php
Session::start(['lifetime' => 3600]);
```

Overrides accept `lifetime`, `path`, `domain`, `secure`, and `samesite`. Each value is validated and invalid overrides are ignored. `httponly` is always forced to `true` and cannot be weakened, even via overrides. Useful in tests and edge cases; prefer config or `.env` for real settings.

## Cookie options

Params come from config first, then `.env`, then a fail-closed default, so setting `.env` alone takes effect without a config key. See `config/config.md` for the override lines.

| Option | Config key | Env var | Default | Notes |
| --- | --- | --- | --- | --- |
| lifetime | `sessionLifetime` | `SESSION_LIFETIME` | `0` | Seconds. `0` means until the browser closes. Must be `>= 0`. |
| path | `cookiePath` | `COOKIE_PATH` | `/` | Must be a non-empty string. |
| domain | `cookieDomain` | `COOKIE_DOMAIN` | `''` | Empty means host-only. |
| secure | `cookieSecure` | `COOKIE_SECURE` | from URL scheme | `true` on `https` `APP_URL`, `false` on plain `http` so local dev works, `true` when the base URL is missing. Explicit `1`/`0` always wins. |
| httponly | - | - | `true` | Always on, not configurable. |
| samesite | `cookieSameSite` | `COOKIE_SAMESITE` | `Lax` | Allowed: `Lax`, `Strict`, `None`. `None` requires `Secure`. |

```bash
# .env
COOKIE_PATH=/
COOKIE_DOMAIN=
COOKIE_SECURE=
COOKIE_SAMESITE=Lax
SESSION_LIFETIME=0
```

`SESSION_LIFETIME=0` keeps the default: the session cookie expires when the browser closes. Set seconds (for example `3600`) for a persistent session.

## Preventing fixation with regenerate()

Call `Session::regenerate()` when privilege changes, normally right after verifying credentials and before storing the user id. It wraps `session_regenerate_id(true)`, deletes the old session data by default, and never throws.

```php
use App\Core\Session;
use App\Core\Storage;

if ($loginOk) {
    Session::regenerate();
    Storage::setSession('user_id', $user->id);
}
```

Pass `false` to keep the old session data: `Session::regenerate(false)`. Returns `false` when no session is active or regeneration fails.

## Reading and writing session data

`Storage::setSession()` starts the session for you and returns `false` when startup fails. The value is only stored when the session is active.

```php
use App\Core\Storage;

Storage::setSession('user_email', 'a@b.com');
$email = Storage::getSession('user_email'); // value, or false when missing
$loggedIn = Storage::hasSession('user_email'); // bool
Storage::deleteSession('user_email'); // bool, true when a value was removed
```

`getSession()` returns `false` when the key is missing. A stored `null` also reads as `false`, so use `hasSession()` when you need to tell missing apart from a stored falsy value.

## Flash and temp data

Flash data lives under a `_temp_` session key for one request: set it, redirect, read it on the next page.

```php
use App\Core\Storage;

Storage::temp('notice', 'Saved'); // always true
echo Storage::getTemp('notice'); // value, or false when missing
Storage::removeTemp(); // true when flash data was removed
```

## Login and logout

```php
Session::regenerate();
Storage::setSession('user_id', $user->id);
// ...
Storage::deleteSession('user_id');
Storage::removeTemp();
```
