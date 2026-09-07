# Ticket B17 - Fix parseBasicTemplate regex injection

Status: Open

Order: 31 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B17

## Problem

`app/Utils/functions.php:305-316` builds `"/{{$key}}/"` without `preg_quote`, so keys with regex chars break or match wrong text.

## Location

`app/Utils/functions.php:305-316`

## Suggested fix

Use `str_replace('{{'.$key.'}}', $value, $string)` or quoted pattern, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
