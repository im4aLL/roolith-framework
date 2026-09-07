# Ticket A7 - Stop trusting proxy IP headers unconditionally

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
