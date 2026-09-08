# Ticket C4 - Replace CMS constant includes and installer.zip with explicit package or env flag

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4 merged with 053-F5: constant.php APP_ENABLE_CMS now reads APP_ENABLE_CMS env flag (default off, .env.example documents it), System.php cms-constant.php kept as legacy seam with release-asset comment, functions.php plus routes.php guard missing CMS files with is_file so core boots without asset, docs/cms-installer.md plus ARCHITECTURE plus routing plus getting-started document env flag. Verified: composer test 178 OK, unset flag boots core-only.

Review follow-up: System.php cms-constant.php guard is now is_file plus is_readable for parity with routes.php and functions.php. Verified: composer test green.

Owner decision Sep 2026: kept tracked per owner decision, excluded from create-project via composer.json archive.exclude + .gitattributes export-ignore, verified with composer archive + git archive. installer.zip stays tracked (see 053-F5), .gitignore not used for it, create-project artifact excludes it. Verified: composer archive zip has no installer.zip, git archive HEAD has no installer.zip.

Final re-review Sep 2026: routes.php plus functions.php gates hardened to defined plus APP_ENABLE_CMS plus is_file plus is_readable for parity with System.php; System.php plus functions.php comments reworded to tracked-locally plus excluded-from-dist truth. Verified: php -l clean, composer test 180 OK.

Doc blockers Sep 2026: .env.example:63 reworded to tracked-locally plus excluded-from-dist truth with pointer to documentation/docs/cms-installer.md, routing.md:252 snippet corrected to defined plus flag plus is_file plus is_readable parity with routes.php:80 plus System.php:97 plus functions.php:728. Verified: composer test 180 OK, grep clean, no PHP logic change.
