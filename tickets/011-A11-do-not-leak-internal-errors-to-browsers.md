# Ticket A11 - Do not leak internal errors to browsers

Status: Done

Order: 11 of 67

Category: Security

Severity: High

Source: AUDIT.md item A11

## Problem

`index.php:17-19` prints `$e->getMessage()`, `app/Controllers/Controller.php:40-47` echoes template exception, `app/Http/routes.php:9-14` echoes config exception, all with HTTP 200.

## Location

`index.php:17-19`, `app/Controllers/Controller.php:40-47`, `app/Http/routes.php:9-14`

## Suggested fix

Log full error with trace ID, return generic 500 page in prod with correct status code, only show details in dev, add test.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: ErrorHandler logs full error with class, file, and trace string under the request trace ID and returns generic 500 with escaped trace ID in prod (rethrows only in dev); Controller::view no longer echoes, it rethrows with view context and previous preserved so ErrorHandler returns 500; routes.php bootstrap failure rethrows instead of echoing with 200; tests/ErrorPagesTest.php covers prod generic body with trace in log, missing-view throw without output, and redirect defaults.
