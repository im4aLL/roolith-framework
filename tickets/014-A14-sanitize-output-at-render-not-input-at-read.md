# Ticket A14 - Sanitize output at render, not input at read

Status: Done

Order: 14 of 67

Category: Security

Severity: Medium

Source: AUDIT.md item A14

## Problem

`app/Core/Sanitize.php:41-60` strips tags and runs `htmlentities` on input, destroying legitimate data and risking double escaping, while `Request::input` applies it by default.

## Location

`app/Core/Sanitize.php:41-83`, `app/Core/Request.php:16-33`

## Suggested fix

Keep raw input, validate by type, escape in views with `escape()` helper, keep `Sanitize` only for narrow cases like slug or email, add tests for `O'Reilly`, unicode, and HTML payloads.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Request::input/all now raw (output-at-render), Sanitize narrowed to param/email with any/string/items legacy, escape() helper plus view audit (home/header/404 already escape). Added OutputEscapingTest for OReilly/unicode/HTML. Verified: composer test 192 pass, views audited.

## Notes

Update Status to In Progress when started and to Done when verified.
