# Ticket F6 - Add Apache hardening for prod

Status: Done

Order: 54 of 67

Category: Ops

Severity: Low

Source: AUDIT.md item F6

## Problem

`Dockerfile:6-8` enables `AllowOverride All` with no `ServerTokens Prod`, `ServerSignature Off`, expiry, or compression.

## Location

`Dockerfile:1-8`, `.htaccess:1-4`

## Suggested fix

Add minimal security and caching headers, document HTTPS termination outside container.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: Dockerfile enables headers plus expires plus deflate, sets ServerTokens Prod plus ServerSignature Off plus TraceEnable Off plus FileETag None, adds cache-compression conf for CSS/JS/SVG, COPYs app for prod parity with uploads plus logs ownership, documents HTTPS termination outside container. DOCKER-README documents hardening. Verified: docker compose config OK, composer test 178 OK, Dockerfile inspection shows both confs plus a2enconf.

Review follow-up: apt install uses --no-install-recommends plus curl, image has HEALTHCHECK curl -f http://localhost/, and the build fails fast with a clear message when assets/build/.vite/manifest.json is missing (run npm run build first; DOCKER-README troubleshooting documents it). Verified: composer test green, docker compose config OK for dev plus prod (no daemon here so docker build plus live curl deferred to a Docker host).
