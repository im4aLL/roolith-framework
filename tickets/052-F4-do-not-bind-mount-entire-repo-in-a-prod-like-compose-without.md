# Ticket F4 - Do not bind-mount entire repo in a prod-like compose without note

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: docker-compose.yml header labels it dev-only with down -v warning, new docker-compose.prod.yml runs from COPY with no bind mount plus uploads_data volume for public/uploads and no phpmyadmin, DOCKER-README plus docs/docker.md document dev vs prod. Verified: docker compose -f docker-compose.prod.yml config shows no bind mount plus uploads_data volume, dev config still bind-mounts, composer test 178 OK.

Review follow-up: prod db healthcheck is now CMD-SHELL (same literal-password fix as dev), .dockerignore now excludes .env/.env.* (keeping !.env.example), docker-compose*.yml, and storage/logs/* so COPY . cannot bake local secrets into the prod image. Verified: docker compose -f docker-compose.prod.yml config OK, composer test green.
