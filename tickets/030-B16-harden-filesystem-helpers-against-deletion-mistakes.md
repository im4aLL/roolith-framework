# Ticket B16 - Harden filesystem helpers against deletion mistakes

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: FS refuses empty, /, APP_ROOT, handles dotfiles via scandir and symlinks via unlink, returns false on failure. tests/FSTest.php covers guards plus temp-dir dotfile and symlink cases; composer test 126 OK.

Phase 2 fix: FS::removeFile() now calls assertDeletablePath() like removeDirectory()/removeFilesInDirectory(), refuses directories (non-symlink) with false, and wraps unlink in try/catch returning false instead of warning; class-level PHPDoc added. tests/FSTest.php::testRemoveFileGuardsAndDeletes asserts empty///APP_ROOT throw plus real temp file delete and missing returns false; composer test 142 OK.
