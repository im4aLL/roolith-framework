# Ticket E3 - Document Vite proxy limits

Status: Done

Order: 48 of 67

Category: Frontend

Severity: Low

Source: AUDIT.md item E3

## Problem

`vite.config.mjs:40-46` proxies everything except a few prefixes to `:8080`, which can surprise API or websocket routes.

## Location

`vite.config.mjs:35-48`, `README.md:66-71`

## Suggested fix

Document which paths bypass proxy, add e2e check that PHP session and HMR work together.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: vite.config.mjs proxy comment lists bypassed prefixes plus session plus HMR implication, README plus docs/frontend-workflow.md document bypass list plus no-API-bypass rule plus login-via-8080 browse-via-5173 session check. Verified: composer test 178 OK, vite config inspection shows documented regex.
