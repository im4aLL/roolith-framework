# Ticket A10 - Block direct access to sensitive files

Status: Done

Order: 10 of 67

Category: Security

Severity: High

Source: AUDIT.md item A10

## Problem

`.htaccess:1-4` only rewrites to front controller, does not deny `config/`, `vendor/`, `.git/`, `constant.php`, `cms-constant.php`, `installer.zip`, `*.log`.

## Location

`.htaccess:1-4`, `Dockerfile:6-8`

## Suggested fix

Add `RedirectMatch 404` or `Require all denied` rules for those paths, move docroot to `public/` long term, verify with curl for each path.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: .htaccess returns 404 via RedirectMatch for config|vendor|.git paths and for constant.php, cms-constant.php, installer.zip, .env*, *.log (plus .env* hardening within the same rule); pattern matrix checked in PHP against all ticket paths (blocked) and /, /example, /form, /assets/css/app.css, /catalog (allowed); apachectl configtest Syntax OK; live Apache curl matrix deferred because this environment serves via php -S which does not enforce .htaccess - re-run the curl matrix on an Apache docroot before release.
