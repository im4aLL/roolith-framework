# Ticket B5 - Harden requiredArray, minLength, maxLength, requiredIf

Status: Open

Order: 19 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B5

## Problem

`app/Core/Rules.php:58-179` assumes nested array shape with undefined indexes, calls `count(null)`, `strlen(null)`, splits condition on `:` which breaks values containing colons, uses loose compare without documented operators.

## Location

`app/Core/Rules.php:58-179`

## Suggested fix

Guard with `isset` and `is_array`, use `mb_strlen`, define strict condition struct instead of colon string, document operators, add tests.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
