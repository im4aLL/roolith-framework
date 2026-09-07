# Ticket A11 - Do not leak internal errors to browsers

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
