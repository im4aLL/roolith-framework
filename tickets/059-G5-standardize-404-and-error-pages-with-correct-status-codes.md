# Ticket G5 - Standardize 404 and error pages with correct status codes

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: unknown routes render views/404.php with HTTP 404 (router path, asserted live and in tests/ErrorPagesTest.php); forced exceptions return HTTP 500 with trace ID; redirect() and redirectToRoute() keep deliberate 303 defaults for Post/Redirect/Get with docblock rationale while Request::redirect uses explicit 302 with CRLF stripping; Controller::view keeps string|bool signature for BC but throws instead of returning false.
