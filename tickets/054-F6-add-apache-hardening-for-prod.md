# Ticket F6 - Add Apache hardening for prod

Status: Open

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

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
