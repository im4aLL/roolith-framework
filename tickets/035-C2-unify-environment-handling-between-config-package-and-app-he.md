# Ticket C2 - Unify environment handling between Config package and app helpers

Status: Done

Order: 35 of 67

Category: Architecture

Severity: High

Source: AUDIT.md item C2

## Problem

`vendor/roolith/config/src/Config.php:42-98` uses `ROOLITH_ENVIRONMENT` process env with `local` default and silent null for missing keys, while `app/Utils/functions.php:249-270` checks `ROOLITH_ENV` constant with dev default, so error mode and config env can diverge.

## Location

`vendor/roolith/config/src/Config.php:42-98`, `app/Utils/functions.php:249-270`, `constant.php:6`

## Suggested fix

Pick one source (`APP_ENV` env var with `production` default safe), bridge constant to `Config::setEnv`, document precedence, fail loudly for invalid env.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 env epic with 001-A1 plus 049-F1, 2026-09-07)

- `App\Core\Env` is the single source (`APP_ENV`, `production` default); `constant.php` bridges `ROOLITH_ENV`; `bootstrap()` calls `Config::setEnv(Env::appEnv())`.
- Precedence documented in `Env` docblock; invalid `APP_ENV` fails loudly with helpful wrapped exception.
- Any env name allowed (staging, uat verified booting production-safe); `Env::is()` helper for env-specific checks.

## Notes

Update Status to In Progress when started and to Done when verified.
