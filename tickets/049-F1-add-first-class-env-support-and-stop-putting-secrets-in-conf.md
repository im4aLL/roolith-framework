# Ticket F1 - Add first-class .env support and stop putting secrets in config.php

Status: Open

Order: 49 of 67

Category: Ops

Severity: High

Source: AUDIT.md item F1

## Problem

`config/config.php:18-24` shows DB creds commented out with no env loader, docs mention dotenv as recipe only, risking committed passwords.

## Location

`config/config.php:1-35`, `documentation/*`

## Suggested fix

Add `vlucas/phpdotenv` or native parser, read `DB_HOST`, `DB_NAME`, `APP_URL`, keep `config.php` as mapping from `$_ENV` with defaults, update `DOCKER-README.md`.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
