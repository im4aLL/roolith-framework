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

Phase 4 verify (no regress): hashing plus manifest behavior preserved after 046-E1 move to assets/build and 033-B19 escaping plus HMR fix; VersionTest updated to assets/build canonical paths, manifest still prod-hashed with stable dev names, helpers still prefer manifest without ?v plus fallback with ?v. Verified: npm run build emits hashed js plus css plus manifest, composer test 178 OK.

Review follow-up: viteBuiltAssetUrl() now catches a missing version config, logs via error_log, and falls back to ?v=dev in development or ?v=1.0.0 otherwise, so viteCss/viteJs can no longer 500 on it. Verified: two new VersionTest cases (dev plus prod fallback), composer test 180 OK.
