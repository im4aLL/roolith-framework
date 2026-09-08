# Ticket F3 - Fix Docker credentials drift and add healthchecks

Status: Done

Order: 51 of 67

Category: Ops

Severity: Medium

Source: AUDIT.md item F3

## Problem

`docker-compose.yml:17-21` sets `MYSQL_PASSWORD: secret` for user `roolith` but `DOCKER-README.md:13-22` lists `password: root` for root and `secret` for roolith in different table, `config` example uses root/root, no healthcheck so phpMyAdmin hits DB before ready.

## Location

`docker-compose.yml:1-40`, `DOCKER-README.md:13-61`

## Suggested fix

Single source of creds via `.env`, add `healthcheck: mysqladmin ping`, add `depends_on: condition: service_healthy`, correct docs table.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: docker-compose.yml reads MYSQL_* via ${VAR:-default} (single source .env, .env.example documents MYSQL_* plus DB_HOST=db mapping), db has mysqladmin ping healthcheck with depends_on service_healthy for app plus phpmyadmin, DOCKER-README plus docs/docker.md creds table corrected. Verified: docker compose config shows service_healthy twice plus healthcheck block, custom MYSQL_ROOT_PASSWORD=custom123 interpolates, composer test 178 OK.

Review follow-up: healthcheck test is now CMD-SHELL so $MYSQL_ROOT_PASSWORD expands (plain CMD passed it literally). Verified: docker compose config renders CMD-SHELL, composer test green.
