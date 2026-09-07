# Ticket A5 - Harden Storage::setCookie and deleteCookie

Status: Open

Order: 5 of 67

Category: Security

Severity: High

Source: AUDIT.md item A5

## Problem

`app/Core/Storage.php:19-33` omits `path`, `domain`, `secure`, `httponly`, `samesite`, passes raw value, delete does not match path so may not clear.

## Location

`app/Core/Storage.php:19-33`

## Suggested fix

Accept options array from config, use `setcookie($name,$value,['expires'=>...,'path'=>'/','httponly'=>true,'secure'=>true,'samesite'=>'Lax'])`, urlencode values, fix delete to use same path/domain.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
