# Ticket E2 - Version CSS and JS consistently

Status: Open

Order: 47 of 67

Category: Frontend

Severity: Low

Source: AUDIT.md item E2

## Problem

`vite.config.mjs:58-77` names JS without hash and CSS by source basename, relying on `?v=time()` query for busting which some CDNs ignore.

## Location

`vite.config.mjs:57-79`, `app/Utils/functions.php:63-66`

## Suggested fix

Enable content hash in filenames for prod, keep stable names only in dev, update helpers to read manifest if added.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
