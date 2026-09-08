# Ticket D3 - Define model mass-assignment and type rules

Status: Done

Order: 44 of 67

Category: Data

Severity: Medium

Source: AUDIT.md item D3

## Problem

`app/Models/Model.php:9-116` exposes raw `orm()` and `raw()` with no fillable, casts, or per-model validation hook.

## Location

`app/Models/Model.php:9-116`

## Suggested fix

Document allowed pattern, add `$fillable` or DTO example, show validated write path in `WelcomeController` example.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added Model fillable/casts/validationRules/validate/filterFillable/castRow/castRows plus validated write docs in README and models.md. Verified: Phase5Test fillable plus casts pass.

## Notes

Update Status to In Progress when started and to Done when verified.
