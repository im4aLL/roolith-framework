# Ticket F3 - Fix Docker credentials drift and add healthchecks

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
