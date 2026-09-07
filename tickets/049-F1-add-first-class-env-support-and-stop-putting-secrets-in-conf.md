# Ticket F1 - Add first-class .env support and stop putting secrets in config.php

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 0 env epic with 001-A1 plus 035-C2, 2026-09-07)

- Documented alternative: dependency-free native `.env` parser in `App\Core\Env::load()` (choice recorded in docblock) instead of `vlucas/phpdotenv`.
- `config/config.php` maps `$_ENV` with defaults (`APP_URL`, `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`, `FORCE_NON_WWW`, `APP_VERSION`, `LOG_PATH`, `LOG_ENABLED`); `.env.example` ships.
- Real env vars take precedence over the `.env` file; nothing secret is committed.

## Notes

Update Status to In Progress when started and to Done when verified.
