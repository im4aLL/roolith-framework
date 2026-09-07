# Ticket D3 - Define model mass-assignment and type rules

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
