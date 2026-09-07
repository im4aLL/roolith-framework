# Ticket B18 - Replace insecure ID generators with random_bytes

Status: Open

Order: 32 of 67

Category: Reliability

Severity: Low

Source: AUDIT.md item B18

## Problem

`app/Utils/functions.php:202-222` uses `str_shuffle`, `mt_rand`, and `time()` which are predictable and can collide.

## Location

`app/Utils/functions.php:202-222`

## Suggested fix

Use `bin2hex(random_bytes(8))` or `uniqid` with crypto randomness, document format.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
