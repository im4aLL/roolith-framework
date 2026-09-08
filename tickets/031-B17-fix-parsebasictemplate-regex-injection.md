# Ticket B17 - Fix parseBasicTemplate regex injection

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: parseBasicTemplate() already uses str_replace (no regex); tests/UrlAndTemplateTest.php asserts regex-char keys like a.b*c replace literally; composer test 126 OK.
