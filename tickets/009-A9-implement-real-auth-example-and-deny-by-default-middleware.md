# Ticket A9 - Implement real auth example and deny-by-default middleware

Status: Done

Order: 9 of 67

Category: Security

Severity: High

Source: AUDIT.md item A9

## Problem

`app/Middlewares/AuthMiddleware.php:10-13` always returns `true` and no usage example exists, so copy-paste leaves routes unprotected.

## Location

`app/Middlewares/AuthMiddleware.php:10-13`

## Suggested fix

Show session check + redirect to login + `return false`, document attaching middleware in routes, add test for allow and deny paths.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
