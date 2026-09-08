# Ticket B3 - Preserve exception chain and types on bootstrap and DB connect

Status: Done

Order: 17 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B3

## Problem

`app/Core/System.php:43-53` catches `InvalidArgumentException` and generic `Exception` then throws new generic `Exception` with only message, losing code, file, and previous trace.

## Location

`app/Core/System.php:39-56`, `app/Core/System.php:105-117`

## Suggested fix

Throw `new Exception($e->getMessage(), 0, $e)`, do not conflate config-missing with connect-failed, log once.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: System::bootstrap() throws new Exception(msg,0,prev) on every path, splits config-missing vs connect-failed with distinct messages, logs once each; connectToDatabase chains driver errors. composer test 126 OK.

Phase 2 fix: chain contract pinned by tests/SystemTest.php::testBootstrapPreservesExceptionChainWithPrevious which seeds an invalid baseUrl, asserts bootstrap throws App\Core\Exceptions\Exception with non-null previous and baseUrl message; Rules::exists/notExists now throw InvalidArgumentException with chain instead of returning false for consistency. composer test 142 OK.
