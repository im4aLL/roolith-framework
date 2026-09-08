# Ticket G3 - Add static analysis and style checks

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added phpstan.neon level 6 plus baseline, composer lint plus analyse scripts, fixed new-code baseline, CI runs analyse. Verified: composer analyse OK, composer lint OK.

## Notes

Update Status to In Progress when started and to Done when verified.
