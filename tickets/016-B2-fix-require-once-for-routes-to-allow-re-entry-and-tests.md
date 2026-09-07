# Ticket B2 - Fix require_once for routes to allow re-entry and tests

Status: Open

Order: 16 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B2

## Problem

`app/Core/System.php:78` uses `require_once`, second call in same process returns `1` not a router.

## Location

`app/Core/System.php:76-84`

## Suggested fix

Use `require`, validate returned value implements `RouterInterface`, throw clear error otherwise.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
