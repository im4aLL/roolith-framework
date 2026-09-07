# Ticket C7 - Document string-based Controller at method dispatch with safety checks

Status: Open

Order: 40 of 67

Category: Architecture

Severity: Low

Source: AUDIT.md item C7

## Problem

`app/Http/routes.php:23-25` concatenates `WelcomeController::class . "@index"`, no `class_exists` or `method_exists` check until runtime.

## Location

`app/Http/routes.php:23-25`

## Suggested fix

Add route:list lint command or bootstrap assertion, prefer `[$class,$method]` callable syntax in docs and generator.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
