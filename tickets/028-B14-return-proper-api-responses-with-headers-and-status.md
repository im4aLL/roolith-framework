# Ticket B14 - Return proper API responses with headers and status

Status: Open

Order: 28 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B14

## Problem

`app/Core/ApiResponseTransformer.php:22-61` returns PHP array with no JSON encoding, header, or status code, while controllers return mixed `string|bool|array`.

## Location

`app/Core/ApiResponseTransformer.php:1-62`, `app/Controllers/Controller.php:38-47`

## Suggested fix

Add `json($payload,$status,$code)` helper that sets `Content-Type: application/json` and `http_response_code`, standardize controller return to `Response` or string, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
