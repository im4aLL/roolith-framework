# Ticket B11 - Replace time() version with content hash

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
