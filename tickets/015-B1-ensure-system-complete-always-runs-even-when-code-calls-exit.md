# Ticket B1 - Ensure System::complete() always runs even when code calls exit or die

Status: Done

Order: 15 of 67

Category: Reliability

Severity: High

Source: AUDIT.md item B1

## Problem

`app/Utils/functions.php:175-180`, `app/Core/Request.php:195-199`, `app/Core/PreProcessor.php:11-30` call `exit` or `die`, so `System.php:63-69` disconnect and `removeTemp` never run and flash semantics break.

## Location

`app/Core/System.php:63-69`, `app/Utils/functions.php:175-195`, `app/Core/Request.php:195-199`

## Suggested fix

Replace exits with returnable `Response` or thrown `RedirectException`, register `register_shutdown_function` fallback for disconnect and temp cleanup, add test.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
