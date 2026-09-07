# Ticket E3 - Document Vite proxy limits

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
