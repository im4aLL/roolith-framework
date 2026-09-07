# Ticket G1 - Add PHP test harness and first regression tests

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
