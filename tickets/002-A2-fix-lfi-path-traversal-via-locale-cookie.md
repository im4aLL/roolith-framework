# Ticket A2 - Fix LFI/path traversal via locale cookie

Status: Done

Order: 2 of 67

Category: Security

Severity: Critical

Source: AUDIT.md item A2

## Problem

`app/Core/Settings.php:28-33` returns raw `Request::cookie('lang')`, `app/Core/Language.php:22-31` does `APP_ROOT . '/lang/' . $lang . '/message.php'` then `include`, so `../` sequences can include arbitrary PHP.

## Location

`app/Core/Language.php:22-31`, `app/Core/Settings.php:18-33`, `app/Utils/Str.php:16-23`

## Suggested fix

Allowlist locales from `lang/*` directory or regex `^[a-z]{2}(-[A-Z]{2})?$`, fallback to `en`, add test with `../../` payload.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Language::sanitizeLang enforces regex ^[a-z]{2}(-[A-Z]{2})?$ plus lang/ directory allowlist with en fallback; Settings::getLang/setLang sanitize; tests/LanguageTest.php (6 tests) covers ../../etc/passwd, ../en, non-string, and unknown locales; live curl -b 'lang=../../etc/passwd' on /example renders English.
