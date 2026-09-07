# Ticket G6 - Tighten Composer version constraints deliberately

Status: Open

Order: 60 of 67

Category: Testing

Severity: Low

Source: AUDIT.md item G6

## Problem

`composer.json:14-22` pins `roolith/database:2.0.0` exact and `nesbot/carbon:2.73.0` exact while others are caret, causing update friction.

## Location

`composer.json:13-24`

## Suggested fix

Use caret unless exact is intentional, document why, run `composer outdated` and `composer audit` regularly.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
