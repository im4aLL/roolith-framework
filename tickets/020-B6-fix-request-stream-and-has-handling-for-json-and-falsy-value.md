# Ticket B6 - Fix Request stream and has handling for JSON and falsy values

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Request reads php://input once with cache, decodes application/json, has() uses array_key_exists. tests/RequestTest.php asserts has("0"), JSON {"a":0} has true plus input 0, and cache per request; composer test 126 OK.

Phase 2 fix: Request::input() now sanitizes stream values consistently with all() - strings via Sanitize::any (so JSON {"comment":"<script>hi"} never returns raw markup), arrays via Sanitize::items, null stays null, int/float/bool preserved for type stability; all() stream documented plus skipSanitization path kept, unsafeInput() documented for raw access. tests/RequestTest.php adds testJsonStringInputIsSanitized, testAllSanitizesStreamUnlessSkipped, testJsonIntInputPreservesType; composer test 142 OK.
