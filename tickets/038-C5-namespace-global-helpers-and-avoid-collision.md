# Ticket C5 - Namespace global helpers and avoid __ collision

Status: Done

Order: 38 of 67

Category: Architecture

Severity: Medium

Source: AUDIT.md item C5

## Problem

`app/Utils/functions.php:15-327` defines global `p`, `url`, `route`, `__`, `redirect`, which collide with gettext and other frameworks and dump HTML even in CLI.

## Location

`app/Utils/functions.php:15-327`

## Suggested fix

Keep thin globals for BC but move logic to `App\Support\*` classes, make `p()` CLI-aware and escape output, consider renaming `__` to `trans()`.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Moved logic to App\Support\Debug, Url, Translator, Redirect, Html, IdGenerator; globals p/url/route/__/redirect/redirectToRoute now thin BC aliases. p() CLI-aware (plain text in CLI, escaped pre in web) with dev-only exit. Added trans() canonical plus __() alias and escape() helper. Verified: composer test 192 pass, Phase5Test support helpers plus trans alias pass.

## Notes

Update Status to In Progress when started and to Done when verified.
