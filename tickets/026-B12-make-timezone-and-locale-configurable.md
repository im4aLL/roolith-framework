# Ticket B12 - Make timezone and locale configurable

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Verified: index.php reads APP_TIMEZONE with UTC default plus validation; Settings::defaultTimezone/defaultLocale read Config then Env with UTC/en defaults and are documented. tests/SettingsAndDbTest.php asserts defaults plus Env honors; composer test 126 OK.

Phase 2 fix: index.php now loads Env before the early timezone read (Env::load(APP_ROOT) then Settings::applyDefaultTimezone()) so a .env-only APP_TIMEZONE is honored instead of falling back to UTC; System::__construct re-applies after Env::load and System::bootstrap re-applies after Config validation so Config `timezone` still wins; new Settings::applyDefaultTimezone(): string installs and returns the zone; Settings::defaultLocale() dead branch (duplicate return) cleaned. tests/SettingsAndDbTest.php::testApplyDefaultTimezoneHonorsEnv asserts Env wins and date_default_timezone_get() updates; composer test 142 OK.
