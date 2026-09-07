# Ticket A8 - Add CSRF protection for state-changing routes

Status: Open

Order: 8 of 67

Category: Security

Severity: High

Source: AUDIT.md item A8

## Problem

No token generation or check found in `Request`, `routes.php`, or `Controller`, `README.md:138-160` shows plain POST form.

## Location

`app/Http/routes.php:1-34`, `app/Core/Request.php:1-270`, `views/**/*.php`

## Suggested fix

Add per-session CSRF token helper, `csrf_field()` view helper, middleware check for POST/PUT/PATCH/DELETE, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
