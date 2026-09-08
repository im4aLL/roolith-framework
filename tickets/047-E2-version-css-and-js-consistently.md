# Ticket E2 - Version CSS and JS consistently

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified with 025-B11: vite.config.mjs hashes entry and CSS/media names only in prod plus manifest:true, stable names in dev; helpers read manifest via viteManifestFile with stable fallback. tests/VersionTest.php asserts manifest hashed plus fallback; asset URL stable in prod; composer test 126 OK.

Phase 2 fix: same as 025-B11 - viteManifest() $GLOBALS cache fully cleared by setViteManifestForTests(null) plus viteClientTag() $GLOBALS with resetViteClientTagForTests() seam; Sanitize::param/email/string now typed string with full PHPDoc and null-safe preg handling. tests/VersionTest.php covers cache-clear and client-tag reset; composer test 142 OK.
