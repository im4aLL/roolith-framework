# Ticket C2 - Unify environment handling between Config package and app helpers

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
