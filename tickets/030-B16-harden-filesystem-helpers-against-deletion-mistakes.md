# Ticket B16 - Harden filesystem helpers against deletion mistakes

Status: Open

Order: 30 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B16

## Problem

`app/Utils/FS.php:51-105` uses `glob` which misses dotfiles, `removeDirectory` and `removeFilesInDirectory` have no root guard and swallow errors.

## Location

`app/Utils/FS.php:51-105`

## Suggested fix

Add path allowlist check to refuse `/`, `APP_ROOT`, empty path, handle dotfiles and symlinks, return detailed result, add tests on temp dir.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
