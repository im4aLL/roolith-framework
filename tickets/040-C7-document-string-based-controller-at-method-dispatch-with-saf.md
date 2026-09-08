# Ticket C7 - Document string-based Controller at method dispatch with safety checks

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added App\Core\RouteValidator with class_exists/method_exists checks, System::assertRouteHandlers bootstrap logging, php roolith route:list lint (exit 1 on invalid). routes.php now prefers [Class, method] callable syntax; docs updated. Verified: php roolith route:list shows 7 routes valid, Phase5Test validator pass.

## Notes

Update Status to In Progress when started and to Done when verified.
