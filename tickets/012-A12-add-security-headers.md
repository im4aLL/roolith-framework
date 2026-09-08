# Ticket A12 - Add security headers

Status: Done

Order: 12 of 67

Category: Security

Severity: Medium

Source: AUDIT.md item A12

## Problem

No `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Strict-Transport-Security` found.

## Location

`index.php:1-19`, `app/Core/System.php:1-167`

## Suggested fix

Send baseline headers from `System` or Apache config with config flags, verify with curl -I.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: System::securityHeaders sends Content-Security-Policy default-src 'self', X-Content-Type-Options nosniff, X-Frame-Options SAMEORIGIN, Referrer-Policy strict-origin-when-cross-origin, Strict-Transport-Security max-age=31536000 includeSubDomains from System::bootstrap via sendSecurityHeaders with SECURITY_HEADERS=0 opt-out; tests/SecurityHeadersTest.php asserts the map; live curl -i shows all five headers on 200, 404, 301, and 405 responses.
