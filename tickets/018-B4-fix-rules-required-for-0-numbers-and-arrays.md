# Ticket B4 - Fix Rules::required for 0, numbers, and arrays

Status: Open

Order: 18 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B4

## Problem

`app/Core/Rules.php:39-48` uses `empty($value)` so `"0"` fails, then `strlen(trim($value))` fatals on array or int edge cases.

## Location

`app/Core/Rules.php:39-48`

## Suggested fix

Explicit null and empty-string check, cast to string only after `is_scalar` check, handle arrays separately, add data-provider test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
