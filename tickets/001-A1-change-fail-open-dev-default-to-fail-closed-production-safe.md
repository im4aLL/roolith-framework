# Ticket A1 - Change fail-open dev default to fail-closed production-safe default

Status: Done

Order: 1 of 67

Category: Security

Severity: Critical

Source: AUDIT.md item A1

## Problem

`constant.php:6` leaves `ROOLITH_ENV` commented out, `app/Utils/functions.php:249-270` returns `isDevEnvironment=true` when undefined, `app/Core/System.php:153-166` enables Whoops pretty pages with paths and stack traces.

## Location

`constant.php:6`, `app/Utils/functions.php:249-270`, `app/Core/System.php:153-166`

## Suggested fix

Default to production-safe errors, require explicit `ROOLITH_ENV=development` or `APP_DEBUG=true` for Whoops, add generic 500 page in prod, add test for both modes.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 env epic with 035-C2 plus 049-F1, 2026-09-07)

- Single `APP_ENV` with `production` default via `app/Core/Env.php`; `ROOLITH_ENV` mirrors it for BC; helpers delegate to `Env`.
- Unset `APP_ENV` boots with `display_errors=0` and generic 500 with trace ID; `APP_ENV=development` shows Whoops.
- `tests/EnvTest.php` covers default/dev/empty/`"0"` handling; `composer test` green; reviewer satisfied.

## Notes

Update Status to In Progress when started and to Done when verified.
