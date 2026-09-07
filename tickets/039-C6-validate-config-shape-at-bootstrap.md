# Ticket C6 - Validate config shape at bootstrap

Status: Done

Order: 39 of 67

Category: Architecture

Severity: Medium

Source: AUDIT.md item C6

## Problem

`Config::get` returns null for missing keys per `vendor/roolith/config/src/Config.php:275-288`, so missing `baseUrl`, `database`, or `version` fails late with unclear errors.

## Location

`app/Core/System.php:39-56`, `config/config.php:1-35`

## Suggested fix

Add `ConfigValidator` asserting types for `baseUrl`, `database|null`, `version`, `forceNonWww`, throw with helpful message listing expected shape.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0, 2026-09-07)

- `app/Core/ConfigValidator.php` asserts `baseUrl` (non-empty), `database|null`, `version` (non-empty string/number), `forceNonWww` (bool), plus `logPath` and `logEnabled` extras, each with helpful message naming the key and the `.env` fix.
- Called in `System::bootstrap()` before DB connect; missing `baseUrl` throws actionable message verified manually.
- `tests/ConfigValidatorTest.php` covers all branches; reviewer satisfied.

## Notes

Update Status to In Progress when started and to Done when verified.
