# Ticket A7 - Stop trusting proxy IP headers unconditionally

Status: Done

Order: 7 of 67

Category: Security

Severity: High

Source: AUDIT.md item A7

## Problem

`app/Utils/functions.php:277-296` prefers `HTTP_CLIENT_IP` and `X_FORWARDED_FOR` which clients can spoof, affecting rate limiting and logs.

## Location

`app/Utils/functions.php:277-296`

## Suggested fix

Only trust proxy headers from configured trusted proxies, otherwise use `REMOTE_ADDR`, parse first IP in `X-Forwarded-For` and validate with `filter_var`.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: getIpAddress returns REMOTE_ADDR unless it is in config trustedProxies (TRUSTED_PROXIES, default empty means trust none); trusted path parses the first IP of X-Forwarded-For and friends and validates with filter_var, falling back to REMOTE_ADDR; return type narrowed to string; tests/TrustedProxyTest.php (4 tests) covers spoof ignored, first-IP honored, invalid fallback, and missing REMOTE_ADDR.
