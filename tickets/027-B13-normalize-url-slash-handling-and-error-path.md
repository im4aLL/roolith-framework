# Ticket B13 - Normalize url() slash handling and error path

Status: Done

Order: 27 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B13

## Problem

`app/Utils/functions.php:32-39` does `Config::get("baseUrl") . $path` with no slash trim, and silently returns raw path on config error hiding misconfiguration.

## Location

`app/Utils/functions.php:32-39`

## Suggested fix

Use `rtrim($base,'/').'/'.ltrim($path,'/')`, throw or log when `baseUrl` missing in dev, add test.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: url() joins via rtrim/ltrim, logs plus throws in dev when baseUrl missing. tests/UrlAndTemplateTest.php asserts slash normalization; composer test 126 OK.
