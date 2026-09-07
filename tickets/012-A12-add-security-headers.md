# Ticket A12 - Add security headers

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
