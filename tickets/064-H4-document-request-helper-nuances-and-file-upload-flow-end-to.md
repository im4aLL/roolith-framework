# Ticket H4 - Document request helper nuances and file upload flow end to end

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
