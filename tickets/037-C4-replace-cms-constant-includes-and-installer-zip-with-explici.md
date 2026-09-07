# Ticket C4 - Replace CMS constant includes and installer.zip with explicit package or env flag

Status: Open

Order: 37 of 67

Category: Architecture

Severity: Medium

Source: AUDIT.md item C4

## Problem

`constant.php:23`, `app/Core/System.php:21-24`, `app/Utils/functions.php:332-334`, `app/Http/routes.php:30-32` branch on `APP_ENABLE_CMS` and optional `cms-constant.php` plus binary `installer.zip` in repo, which is opaque and hard to version.

## Location

`constant.php:23`, `app/Core/System.php:21-24`, `app/Utils/functions.php:332-334`, `app/Http/routes.php:30-32`

## Suggested fix

Move CMS to composer package or documented module, use env flag `APP_ENABLE_CMS`, remove zip from git or publish checksum and contents list.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
