# Ticket H2 - Keep single source for architecture to avoid drift

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- ARCHITECTURE.md marked canonical, docs/architecture.md mirror note, README pointer, CONTRIBUTING sync rule. Verified: both files carry canonical note.

## Notes

Update Status to In Progress when started and to Done when verified.
