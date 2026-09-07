# Ticket A1 - Change fail-open dev default to fail-closed production-safe default

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
