# Ticket B19 - Fix Vite helper HMR edge case

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: viteJs now emits shared viteClientTag in dev (JS-only pages get HMR, single client across viteCss plus viteJs), all dev plus prod URLs escaped via htmlspecialchars, viteClientTag escapes server URL. Verified: new tests/ViteHmrTest.php 4 tests prove JS-first plus CSS-first single client plus dev plus prod escaping, composer test 178 OK (11 Vite plus Version tests pass).
