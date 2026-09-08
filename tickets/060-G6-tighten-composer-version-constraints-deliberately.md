# Ticket G6 - Tighten Composer version constraints deliberately

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Changed roolith/database 2.0.0 to ^2.0 and nesbot/carbon 2.73.0 to ^2.73 with caret rationale in README/SECURITY plus ARCHITECTURE. Verified: composer audit passes (no advisories).

## Notes

Update Status to In Progress when started and to Done when verified.
