# Ticket C6 - Validate config shape at bootstrap

Status: Open

Order: 39 of 67

Category: Architecture

Severity: Medium

Source: AUDIT.md item C6

## Problem

`Config::get` returns null for missing keys per `vendor/roolith/config/src/Config.php:275-288`, so missing `baseUrl`, `database`, or `version` fails late with unclear errors.

## Location

`app/Core/System.php:39-56`, `config/config.php:1-35`

## Suggested fix

Add `ConfigValidator` asserting types for `baseUrl`, `database|null`, `version`, `forceNonWww`, throw with helpful message listing expected shape.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
