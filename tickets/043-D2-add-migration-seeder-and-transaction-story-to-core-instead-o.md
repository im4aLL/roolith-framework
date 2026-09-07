# Ticket D2 - Add migration, seeder, and transaction story to core instead of docs-only

Status: Open

Order: 43 of 67

Category: Data

Severity: Medium

Source: AUDIT.md item D2

## Problem

`ARCHITECTURE.md:160-161` lists migrations and Cycle ORM as documented patterns, but default app has no runner, so schema drifts.

## Location

`ARCHITECTURE.md:160-161`, `documentation/*`

## Suggested fix

Add minimal `php roolith migrate` or adopt existing tool, add `DB::transaction(fn)` helper, document model-to-table contract.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
