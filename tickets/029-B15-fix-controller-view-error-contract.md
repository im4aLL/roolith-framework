# Ticket B15 - Fix Controller::view error contract

Status: Done

Order: 29 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B15

## Problem

`app/Controllers/Controller.php:38-47` echoes on failure and returns `false`, caller then returns `false` to router which expects string, type is `string|bool` which is awkward.

## Location

`app/Controllers/Controller.php:38-47`

## Suggested fix

Throw or return 500 response, change signature to `string`, log template errors, add test for missing view.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
