# Ticket G5 - Standardize 404 and error pages with correct status codes

Status: Open

Order: 59 of 67

Category: Testing

Severity: Medium

Source: AUDIT.md item G5

## Problem

`ARCHITECTURE.md:119-120` says router renders `views/404.php` but no status code handling shown, error helpers return 303 for redirects by default which is unusual for POST.

## Location

`ARCHITECTURE.md:119-120`, `app/Utils/functions.php:175-195`

## Suggested fix

Ensure 404 sends 404, 500 sends 500, use 302 or 303 deliberately with comment, add test asserting codes.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
