# Ticket D1 - Do not force debugMode(false) globally

Status: Open

Order: 42 of 67

Category: Data

Severity: High

Source: AUDIT.md item D1

## Problem

`app/Core/DatabaseFactory.php:22` always disables debug, hiding SQL errors in dev and making failures return false with no log.

## Location

`app/Core/DatabaseFactory.php:16-23`

## Suggested fix

Tie debug to env, log queries in dev, throw or log in prod, document how to enable query log per request.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
