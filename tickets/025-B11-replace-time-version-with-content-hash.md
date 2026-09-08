# Ticket B11 - Replace time() version with content hash

Status: Done

Order: 25 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B11

## Problem

`config/config.php:34` sets `"version" => time()`, so `viteBuiltAssetUrl` changes every request and kills browser and CDN caching.

## Location

`config/config.php:34`, `app/Utils/functions.php:63-66`, `app/Utils/functions.php:324-327`

## Suggested fix

Use `filemtime` of built asset or git short hash or static `APP_VERSION`, fallback to `time()` only in dev.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified with 047-E2: config version uses AppVersion::resolve() (filemtime, else git hash, else time() only in dev else 1.0.0); explicit APP_VERSION wins. tests/VersionTest.php asserts stable prod URL plus static fallback; composer test 126 OK.

Phase 2 fix: AppVersion PHPDoc now notes scope - only front entries (assets/css/app.css, assets/js/app.js) drive mtime while optional admin entries from installer.zip resolve via the Vite manifest and reuse the front version for the query fallback; viteManifest() cache moved from static to $GLOBALS with setViteManifestForTests(null) clearing both override and file cache; viteClientTag() render-once moved to $GLOBALS with resetViteClientTagForTests() seam. tests/VersionTest.php adds testViteManifestCacheClearedByNull (real manifest rewrite proving stale-until-clear then fresh-after-clear) plus testViteClientTagResetSeam; composer test 142 OK.

Simplification: AppVersion class removed per owner request. config version is now explicit APP_VERSION, else time() in development else static 1.0.0 in prod. Dev uses time() for no-cache, prod user sets a fixed version. tests/VersionTest.php AppVersion test removed; composer test 141 OK.
