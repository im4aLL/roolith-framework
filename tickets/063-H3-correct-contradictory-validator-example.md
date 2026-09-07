# Ticket H3 - Correct contradictory validator example

Status: Open

Order: 63 of 67

Category: Docs

Severity: Medium

Source: AUDIT.md item H3

## Problem

`README.md:163-180` shows `name => Rules::set()->isRequired()->minLength(10)->isArray()->maxLength(20)` which cannot pass for a string and confuses `notExists` usage.

## Location

`README.md:163-185`

## Suggested fix

Split into string example and array example, show `errors()` output, link to `Validator.php:8-31` usage block after fixing it to match.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
