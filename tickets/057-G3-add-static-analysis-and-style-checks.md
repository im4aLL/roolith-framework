# Ticket G3 - Add static analysis and style checks

Status: Open

Order: 57 of 67

Category: Testing

Severity: Medium

Source: AUDIT.md item G3

## Problem

No `phpstan`, `psalm`, or `php-cs-fixer` config found, inconsistent spacing like `Carbon::now()->addMonths()` with no arg and docblock types.

## Location

`composer.json`, `app/Core/*`

## Suggested fix

Add `phpstan.neon` level 6+, `composer lint` and `composer analyse`, fix baseline, run in CI.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
