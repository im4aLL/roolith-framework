# Ticket B8 - Guard Model::instance and empty table configuration

Status: Done

Order: 22 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B8

## Problem

`app/Models/Model.php:69-105` returns `false` on reflection failure but callers chain `self::instance()->getAll()` which fatals, and empty `$table` produces invalid SQL late.

## Location

`app/Models/Model.php:9-116`

## Suggested fix

Throw on failure instead of returning false, assert non-empty table in `getOrm`, add test for misconfigured model.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: Model::instance() throws RuntimeException with chain instead of false, getOrm asserts non-empty table. tests/ModelGuardTest.php asserts misconfigured model throws; composer test 126 OK.
