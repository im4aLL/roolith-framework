# Ticket G2 - Add PSR-3 logging and use it on all error paths

Status: Done

Order: 56 of 67

Category: Testing

Severity: High

Source: AUDIT.md item G2

## Problem

No logger found, prod sets `log_errors=1` with no path in `System.php:155-160`, bootstrap and controller errors are echoed not logged.

## Location

`app/Core/System.php:153-160`, `index.php:17-19`

## Suggested fix

Add `monolog/monolog`, log bootstrap, DB, router, and 404 with context and trace ID, document where logs go in Docker.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 skeleton, 2026-09-07)

- Documented alternative: minimal PSR-3 file logger (`App\Core\Logger`, `psr/log`) instead of monolog; every line carries trace ID; wired to bootstrap and exception handler only.
- Routine logs off by default (`LOG_ENABLED`/`logEnabled`); warning and above always written so crash visibility is kept.
- `tests/LoggerTest.php` plus `tests/ErrorHandlerTest.php`; logs show correlated bootstrap entries; reviewer satisfied.
- Full error-path coverage (router, 404, controllers) deferred to Phase 5 per plan.

## Verification (Phase 5 full, 2026-09-08)

- Full coverage: `App\Core\Log` shared holder set by `System`, `Url` plus `Request` plus `Controller` plus `System::router` plus 404 logging on same trace stream, `ErrorHandler` unchanged. Verified: `composer test` 192 pass, logs show router plus 404 plus view lines.

## Notes

Update Status to In Progress when started and to Done when verified.
