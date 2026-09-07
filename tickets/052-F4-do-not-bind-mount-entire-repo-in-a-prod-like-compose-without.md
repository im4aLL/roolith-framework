# Ticket F4 - Do not bind-mount entire repo in a prod-like compose without note

Status: Open

Order: 52 of 67

Category: Ops

Severity: Medium

Source: AUDIT.md item F4

## Problem

`docker-compose.yml:9` mounts `./:/var/www/html` which is convenient for dev but hides permission and persistence issues.

## Location

`docker-compose.yml:9`

## Suggested fix

Label file as dev override, add prod example with `COPY` build and named volume for uploads, document `down -v` data loss warning already in DOCKER-README but repeat at top.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
