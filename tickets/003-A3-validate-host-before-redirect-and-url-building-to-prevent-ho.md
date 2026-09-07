# Ticket A3 - Validate Host before redirect and URL building to prevent Host header injection and open redirect

Status: Open

Order: 3 of 67

Category: Security

Severity: Critical

Source: AUDIT.md item A3

## Problem

`app/Core/PreProcessor.php:11-30` and `app/Core/Request.php:250-254` trust `$_SERVER['HTTP_HOST']` and `REQUEST_URI` directly for `Location` headers.

## Location

`app/Core/PreProcessor.php:11-30`, `app/Core/Request.php:250-254`

## Suggested fix

Compare host against `baseUrl` host allowlist, use 301/308 with encoded URI, drop or log mismatched hosts, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
