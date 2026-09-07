# Ticket F5 - Remove or replace installer.zip binary in git

Status: Open

Order: 53 of 67

Category: Ops

Severity: Medium

Source: AUDIT.md item F5

## Problem

Root `installer.zip` is opaque, bloats clones, cannot review.

## Location

`/installer.zip` (repo root), `ARCHITECTURE.md:189`

## Suggested fix

Publish as release asset with checksum or composer package, add script to fetch on demand, remove from repo history if sensitive.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
