# Ticket A13 - Move rate limiting off session to survive cookie clear and multi-server

Status: Done

Order: 13 of 67

Category: Security

Severity: Medium

Source: AUDIT.md item A13

## Problem

`app/Core/SessionRateLimiter.php:29-45` stores attempts in `$_SESSION`, attacker clears cookie for fresh bucket, does not work across servers.

## Location

`app/Core/SessionRateLimiter.php:1-55`

## Suggested fix

Short term document limits, long term add file/cache/DB driver using `roolith/cache`, fix side-effect where check also increments and blocked path does not prune expired entries.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
