# Ticket H2 - Keep single source for architecture to avoid drift

Status: Open

Order: 62 of 67

Category: Docs

Severity: Medium

Source: AUDIT.md item H2

## Problem

`README.md:13` points to both `ARCHITECTURE.md` and `documentation/docs/architecture.md`, which can diverge.

## Location

`README.md:13`, `ARCHITECTURE.md:1-227`

## Suggested fix

Keep `ARCHITECTURE.md` canonical and symlink or import into VitePress, add CI check for sync or note canonical file at top of both.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
