# Ticket D1 - Do not force debugMode(false) globally

Status: Done

Order: 42 of 67

Category: Data

Severity: High

Source: AUDIT.md item D1

## Problem

`app/Core/DatabaseFactory.php:22` always disables debug, hiding SQL errors in dev and making failures return false with no log.

## Location

`app/Core/DatabaseFactory.php:16-23`

## Suggested fix

Tie debug to env, log queries in dev, throw or log in prod, document how to enable query log per request.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: DatabaseFactory ties debugMode to APP_ENV via isDebugEnabled() (dev on, prod off) with per-request override documented. tests/SettingsAndDbTest.php asserts dev true plus prod false; composer test 126 OK.

Phase 2 fix: DatabaseFactory::getInstance() now applies the env default only on creation (plus RouterFactory-style reset() production method with resetForTests() alias) so a per-request ->debugMode(true) override survives later getInstance() calls instead of being overwritten every call. tests/SettingsAndDbTest.php::testDbFactoryPreservesPerRequestOverride arms a DatabaseInterface spy and asserts no debugMode() call on reuse; composer test 142 OK.
