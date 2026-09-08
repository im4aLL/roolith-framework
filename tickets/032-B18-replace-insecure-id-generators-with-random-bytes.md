# Ticket B18 - Replace insecure ID generators with random_bytes

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Replaced str_shuffle/mt_rand/time() with App\Support\IdGenerator using bin2hex(random_bytes()). Formats: alphaNumeric 4 upper hex dash 16 hex, uniqueNumber 16 hex dash 8 hex. Verified: Phase5Test regex plus uniqueness pass, grep shows no str_shuffle/mt_rand/time in new code.

## Notes

Update Status to In Progress when started and to Done when verified.
