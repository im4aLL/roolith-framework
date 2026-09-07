# Ticket B13 - Normalize url() slash handling and error path

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
