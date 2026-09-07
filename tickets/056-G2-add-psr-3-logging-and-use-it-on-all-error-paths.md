# Ticket G2 - Add PSR-3 logging and use it on all error paths

Status: Open

Order: 56 of 67

Category: Testing

Severity: High

Source: AUDIT.md item G2

## Problem

No logger found, prod sets `log_errors=1` with no path in `System.php:155-160`, bootstrap and controller errors are echoed not logged.

## Location

`app/Core/System.php:153-160`, `index.php:17-19`

## Suggested fix

Add `monolog/monolog`, log bootstrap, DB, router, and 404 with context and trace ID, document where logs go in Docker.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
