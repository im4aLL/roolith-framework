# Ticket B6 - Fix Request stream and has handling for JSON and falsy values

Status: Open

Order: 20 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B6

## Problem

`app/Core/Request.php:60-87` calls `parse_str(file_get_contents("php://input"))` on every `input` and `has` call, only supports urlencoded not JSON, `has` treats `"0"` or `0` as missing due to truthiness.

## Location

`app/Core/Request.php:60-114`

## Suggested fix

Read `php://input` once, support `application/json` via `json_decode`, fix `has` to use `array_key_exists`, cache result per request, add tests.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
