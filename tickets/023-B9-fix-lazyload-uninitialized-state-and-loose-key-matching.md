# Ticket B9 - Fix LazyLoad uninitialized state and loose key matching

Status: Open

Order: 23 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B9

## Problem

`app/Core/LazyLoad.php:11-56` leaves `$loadArray` uninitialized so `get()` without `with()` warns, accesses `$item->{$dto->foreignKey}` without `isset`, uses strict `===` so int `1` never matches string `"1"`.

## Location

`app/Core/LazyLoad.php:11-108`

## Suggested fix

Init to `[]`, guard missing keys, normalize IDs to string or use loose comparison deliberately, handle empty data early, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
