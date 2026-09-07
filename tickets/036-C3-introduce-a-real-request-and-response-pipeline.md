# Ticket C3 - Introduce a real Request and Response pipeline

Status: Open

Order: 36 of 67

Category: Architecture

Severity: Medium

Source: AUDIT.md item C3

## Problem

Controllers read superglobals via statics and return mixed values or `exit`, middleware only votes `bool` with no `next()` or post-processing.

## Location

`app/Core/Request.php:1-270`, `vendor/roolith/router/src/*Middleware*.php (external, verify path)`, `app/Controllers/Controller.php:1-48`

## Suggested fix

Add immutable `Response($body,$status,$headers)` object, change middleware to `process($request,$next)`, migrate `redirect()` helpers to return responses, keep BC shim.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
