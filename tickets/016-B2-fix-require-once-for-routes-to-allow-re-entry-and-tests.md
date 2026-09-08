# Ticket B2 - Fix require_once for routes to allow re-entry and tests

Status: Done

Order: 16 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B2

## Problem

`app/Core/System.php:78` uses `require_once`, second call in same process returns `1` not a router.

## Location

`app/Core/System.php:76-84`

## Suggested fix

Use `require`, validate returned value implements `RouterInterface`, throw clear error otherwise.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: System::processRequest() uses require plus RouterInterface check (app/Core/System.php:187-198). FacadeResetTest proves RouterFactory isolation; composer test 126 OK.

Phase 2 fix: System::processRequest() now calls RouterFactory::reset() before require (RouterFactory::reset() production method, resetForTests() alias) so re-entry starts from an empty route table instead of double-registering on the shared singleton; constant.php guards redefined so System is constructible in-process. tests/SystemTest.php::testProcessRequestReEntryDoesNotDuplicateRoutes buffers router output, calls processRequest() twice, and asserts stable route count; composer test 142 OK.
