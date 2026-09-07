# Ticket A10 - Block direct access to sensitive files

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
