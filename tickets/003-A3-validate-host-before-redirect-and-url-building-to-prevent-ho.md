# Ticket A3 - Validate Host before redirect and URL building to prevent Host header injection and open redirect

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: PreProcessor validates Host against baseUrl allowlist (base host plus www/bare counterpart, port-insensitive), canonical redirects use 301 with percent-encoded URI and CRLF stripping, mismatches are logged via PSR-3 logger with error_log fallback and dropped without redirect; Request::fullUrl falls back to baseUrl host on mismatch; tests/HostValidationTest.php (5 tests); live curl -H 'Host: evil.com' returns 200 with no Location to evil, curl -H 'Host: www.localhost' returns 301 to http://localhost/ with encoded path, mismatch warnings appear in storage/logs/app.log.
