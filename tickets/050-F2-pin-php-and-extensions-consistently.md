# Ticket F2 - Pin PHP and extensions consistently

Status: Done

Order: 50 of 67

Category: Ops

Severity: High

Source: AUDIT.md item F2

## Problem

`composer.json:23` allows `>=8.0` but code uses `mixed`, `static` return, `str_ends_with` which need 8.0 plus, `Dockerfile:1` uses `8.2-apache` with only `pdo_mysql,mysqli`, missing `intl,zip,opcache`.

## Location

`composer.json:13-24`, `Dockerfile:1-8`

## Suggested fix

Require `>=8.2`, add needed extensions, enable opcache in prod image, add `composer check-platform-reqs` to CI.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0, 2026-09-07)

- `composer.json` requires `php>=8.2`; `Dockerfile` adds `intl,zip,opcache` (`libicu-dev`/`libzip-dev`); `composer check-platform-reqs` documented and clean (11/11).
- `composer check-platform-reqs` runs in CI; reviewer satisfied.

## Notes

Update Status to In Progress when started and to Done when verified.
