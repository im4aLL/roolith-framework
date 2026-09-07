# Ticket D4 - Optimize LazyLoad from O(n*m) filter to keyed map

Status: Open

Order: 45 of 67

Category: Data

Severity: Low

Source: AUDIT.md item D4

## Problem

`app/Core/LazyLoad.php:94-107` filters full related set per parent item.

## Location

`app/Core/LazyLoad.php:94-107`

## Suggested fix

Index related rows by local key once, then attach, add benchmark test for 1000 rows.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
