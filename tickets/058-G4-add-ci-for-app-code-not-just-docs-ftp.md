# Ticket G4 - Add CI for app code, not just docs FTP

Status: Done

Order: 58 of 67

Category: Testing

Severity: Medium

Source: AUDIT.md item G4

## Problem

`.github/workflows/deploy-web-ftp.yml:1-35` only builds VitePress docs on manual dispatch, no test or build on push.

## Location

`.github/workflows/deploy-web-ftp.yml:1-35`

## Suggested fix

Add `ci.yml` running `composer install`, `composer test`, `npm ci`, `npm run build`, PHP lint on pull requests.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 minimal, 2026-09-07)

- `.github/workflows/ci.yml` runs on push/PR with PHP 8.2/8.3/8.4 matrix: `composer install`, `composer check-platform-reqs`, `composer test`, `php -l`.
- Full CI (`npm build`, `composer audit`) deferred to Phase 5 per plan.

## Verification (Phase 5 full, 2026-09-08)

- Extended `ci.yml` with `composer lint`, `composer analyse`, `composer audit`, `php roolith route:list`, plus frontend job `npm ci` plus `npm run build`. Verified: `npm run build` succeeds, `composer audit` passes.

## Notes

Update Status to In Progress when started and to Done when verified.
