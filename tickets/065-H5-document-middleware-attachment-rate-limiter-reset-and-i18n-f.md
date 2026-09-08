# Ticket H5 - Document middleware attachment, rate limiter reset, and i18n fallback

Status: Done

Order: 65 of 67

Category: Docs

Severity: Medium

Source: AUDIT.md item H5

## Problem

No example shows `$router->middleware()` or equivalent, `SessionRateLimiter::clear` usage missing, `__('errors.required')` fallback when key or locale missing is undocumented.

## Location

`README.md:245-282`, `app/Core/SessionRateLimiter.php:52-54`, `app/Utils/Str.php:16-23`

## Suggested fix

Add three short recipes with code and expected output, note cookie timing for `setLang`.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added middleware attach plus group, SessionRateLimiter::clear, trans fallback plus setLang cookie-timing recipes in README plus localization.md plus storage.md. Verified: docs present.

## Notes

Update Status to In Progress when started and to Done when verified.
