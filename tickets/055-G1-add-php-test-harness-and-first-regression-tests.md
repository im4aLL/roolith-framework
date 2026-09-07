# Ticket G1 - Add PHP test harness and first regression tests

Status: Done

Order: 55 of 67

Category: Testing

Severity: High

Source: AUDIT.md item G1

## Problem

Glob for `**/*test*.php` and `**/phpunit*` finds nothing in framework root, so validator, sanitizer, routing, and file upload changes have no safety net.

## Location

`composer.json`, `phpunit.xml` (new), `tests/` (new), `app/Core/Validator.php`, `app/Core/Rules.php`, `app/Core/Request.php`

## Suggested fix

Add `phpunit/phpunit`, `phpunit.xml`, `tests/` with cases for `Rules::required`, `Validator`, `Sanitize`, `Request::has`, `LazyLoad`, wire `composer test`.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 minimal harness, 2026-09-07)

- `phpunit/phpunit` (require-dev), `phpunit.xml`, `tests/` with `composer test` wired; `composer test` green (34 tests, 68 assertions: Rules, Env, Logger, ConfigValidator, ErrorHandler).
- `tests/` plus `phpunit.xml` are `export-ignore`d so `create-project` users get an empty scaffold, not framework tests.
- Remaining suites (Validator, Sanitize, Request, LazyLoad) deferred to Phase 2 per plan (055b).

## Notes

Update Status to In Progress when started and to Done when verified.
