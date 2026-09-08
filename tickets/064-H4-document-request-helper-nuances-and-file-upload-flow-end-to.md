# Ticket H4 - Document request helper nuances and file upload flow end to end

Status: Done

Order: 64 of 67

Category: Docs

Severity: Medium

Source: AUDIT.md item H4

## Problem

`README.md:143-160` lists `Request::only('page')` but signature is `only($name)` handling string or array via `_::only`, no mention of JSON body, `skipSanitization`, `_files` merging, or `File::upload` destination permissions.

## Location

`README.md:143-160`, `app/Core/Request.php:92-114`

## Suggested fix

Add full form plus JSON plus files example with validation and error display, document `hasFile` and size limits.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Documented only/except string-or-array, JSON body, raw plus escape, _files merging, hasFile plus File limits with full form/JSON/files example in README plus request.md. Verified: docs present.

## Notes

Update Status to In Progress when started and to Done when verified.
