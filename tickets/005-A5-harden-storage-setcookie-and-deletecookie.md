# Ticket A5 - Harden Storage::setCookie and deleteCookie

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Storage::setCookie/deleteCookie use setcookie with options array (expires, path /, domain from config, Secure, HttpOnly true, SameSite Lax) sharing config keys with Session; delete matches path/domain and unsets $_COOKIE; values cast to string and left for setcookie to encode (documented alternative to manual urlencode, which would double-encode); tests/CookieTest.php covers cookieOptions flags plus set/delete round trip.
