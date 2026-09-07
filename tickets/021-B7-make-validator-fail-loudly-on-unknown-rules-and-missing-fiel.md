# Ticket B7 - Make Validator fail loudly on unknown rules and missing fields

Status: Open

Order: 21 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B7

## Problem

`app/Core/Validator.php:54-69` silently skips rule names without matching `Rules` method, so typos pass validation.

## Location

`app/Core/Validator.php:49-73`

## Suggested fix

Throw `InvalidArgumentException` for unknown rule, collect field-missing as failure when `required` present, document behavior.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
