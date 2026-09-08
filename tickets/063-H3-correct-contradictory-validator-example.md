# Ticket H3 - Correct contradictory validator example

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Split README validator into string plus array examples, show errors() output, fixed Validator.php usage block, documented notExists. Verified: README plus Validator.php consistent.

## Notes

Update Status to In Progress when started and to Done when verified.
