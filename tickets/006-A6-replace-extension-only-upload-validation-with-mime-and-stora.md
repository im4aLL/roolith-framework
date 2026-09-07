# Ticket A6 - Replace extension-only upload validation with MIME and storage hardening

Status: Open

Order: 6 of 67

Category: Security

Severity: High

Source: AUDIT.md item A6

## Problem

`app/Core/File.php:45-73` only checks filename extension, size, and `error==0`, no `finfo`, no image re-encode, keeps user filename fragment, `app/Utils/FS.php:13-16` uses `move_uploaded_file` to any caller path which may be web-accessible.

## Location

`app/Core/File.php:13-109`, `app/Utils/FS.php:13-16`

## Suggested fix

Check `mime_content_type`/`finfo`, enforce allowlist of mime+ext, cap by `upload_max_filesize`, generate random names with `random_bytes`, store outside docroot or deny execution via `.htaccess`, add test uploads including double-extension `shell.php.jpg`.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
