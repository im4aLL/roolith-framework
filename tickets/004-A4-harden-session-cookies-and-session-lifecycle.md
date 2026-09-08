# Ticket A4 - Harden session cookies and session lifecycle

Status: Done

Order: 4 of 67

Category: Security

Severity: High

Source: AUDIT.md item A4

## Problem

`index.php:8` calls bare `session_start()` with no `httponly`, `secure`, `samesite`, no `session_regenerate_id()` on privilege change, `app/Core/Storage.php:53-62` may call `session_start()` again.

## Location

`index.php:8`, `app/Core/Storage.php:53-62`, `app/Core/SessionRateLimiter.php:10-22`

## Suggested fix

Set `session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Lax'])` via config, start session once in `System`, regenerate on login, add single `Session::start()` helper.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: new App\Core\Session::start() helper owns startup with session_set_cookie_params httponly true, Secure via config (https default), SameSite Lax, path /; System::bootstrap calls it after config validation, index.php bare session_start removed, Storage::setSession and SessionRateLimiter delegate to it, Session::regenerate available for login privilege change; tests/CookieTest.php covers params; live Set-Cookie shows path=/ HttpOnly SameSite=Lax (Secure only on https by design so local http sessions still work).
