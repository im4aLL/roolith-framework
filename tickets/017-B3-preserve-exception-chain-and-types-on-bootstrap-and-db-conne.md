# Ticket B3 - Preserve exception chain and types on bootstrap and DB connect

Status: Open

Order: 17 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B3

## Problem

`app/Core/System.php:43-53` catches `InvalidArgumentException` and generic `Exception` then throws new generic `Exception` with only message, losing code, file, and previous trace.

## Location

`app/Core/System.php:39-56`, `app/Core/System.php:105-117`

## Suggested fix

Throw `new Exception($e->getMessage(), 0, $e)`, do not conflate config-missing with connect-failed, log once.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
