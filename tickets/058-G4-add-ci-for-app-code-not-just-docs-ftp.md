# Ticket G4 - Add CI for app code, not just docs FTP

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
