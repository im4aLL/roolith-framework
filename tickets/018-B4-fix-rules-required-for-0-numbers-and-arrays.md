# Ticket B4 - Fix Rules::required for 0, numbers, and arrays

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Rules::required() uses explicit null check, trim only on strings (is_scalar guard), arrays separately. tests/RulesTest.php data provider asserts passes for "0", 0, [0] and fails for null, "", []; composer test 126 OK.
