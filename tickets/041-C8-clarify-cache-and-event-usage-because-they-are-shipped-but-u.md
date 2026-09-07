# Ticket C8 - Clarify cache and event usage because they are shipped but unwired

Status: Open

Order: 41 of 67

Category: Architecture

Severity: Low

Source: AUDIT.md item C8

## Problem

`ARCHITECTURE.md:177` says cache and event are on-demand, but no example in `README.md` or `app/` shows when to use them.

## Location

`ARCHITECTURE.md:171-177`, `composer.json:14-21`

## Suggested fix

Add one caching example for config or model query and one event example for user created, or remove unused deps from default install.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
