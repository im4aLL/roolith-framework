# Ticket B19 - Fix Vite helper HMR edge case

Status: Open

Order: 33 of 67

Category: Reliability

Severity: Low

Source: AUDIT.md item B19

## Problem

`app/Utils/functions.php:74-122` only emits `viteClientTag` from `viteCss`, so JS-only pages miss HMR client, and URLs are not escaped.

## Location

`app/Utils/functions.php:74-122`

## Suggested fix

Ensure client tag emitted once when either helper runs in dev, escape URLs with `htmlspecialchars`, add manual dev check.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
