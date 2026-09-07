# Ticket H5 - Document middleware attachment, rate limiter reset, and i18n fallback

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
