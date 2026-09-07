# Ticket B12 - Make timezone and locale configurable

Status: Open

Order: 26 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B12

## Problem

`index.php:6` hardcodes `America/Edmonton`, `Settings` defaults to `en` with no config.

## Location

`index.php:6`, `app/Core/Settings.php:9-10`

## Suggested fix

Read `timezone` and `locale` from config or env with sane default `UTC`, document change.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
