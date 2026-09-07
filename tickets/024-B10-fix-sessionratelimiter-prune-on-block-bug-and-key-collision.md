# Ticket B10 - Fix SessionRateLimiter prune-on-block bug and key collision

Status: Open

Order: 24 of 67

Category: Reliability

Severity: Medium

Source: AUDIT.md item B10

## Problem

`app/Core/SessionRateLimiter.php:29-45` filters expired entries but only persists when not blocked, so blocked buckets never shrink, and generic `rate_limit` session key may collide.

## Location

`app/Core/SessionRateLimiter.php:4-54`

## Suggested fix

Always persist pruned list, namespace key per feature, separate `hit()` from `tooManyAttempts()` check, add time-mocked test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
