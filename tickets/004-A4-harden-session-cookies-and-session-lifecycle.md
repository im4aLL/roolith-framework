# Ticket A4 - Harden session cookies and session lifecycle

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
