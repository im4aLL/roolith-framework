# Ticket C5 - Namespace global helpers and avoid __ collision

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
