# Ticket C8 - Clarify cache and event usage because they are shipped but unwired

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added App\Examples\CacheAndEventExamples with cachedModelQuery/cachedConfig plus userCreated event, README plus ARCHITECTURE 6.11 docs for when to use. Verified: Phase5Test cache plus event pass.

## Notes

Update Status to In Progress when started and to Done when verified.
