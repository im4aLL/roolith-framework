# Ticket C1 - Reduce static facades with explicit seams and resettable singletons

Status: Open

Order: 34 of 67

Category: Architecture

Severity: High

Source: AUDIT.md item C1

## Problem

`Request`, `Storage`, `Sanitize`, `Config`, `RouterFactory`, `DatabaseFactory`, `TemplateEngineFactory`, `Lang` are statics or singletons with no `reset()`, making tests order-dependent and hiding dependencies.

## Location

`app/Core/RouterFactory.php:13-20`, `app/Core/DatabaseFactory.php:16-23`, `app/Core/TemplateEngineFactory.php:18-24`, `app/Core/Lang.php:11-18`

## Suggested fix

Add `resetForTests()` to each factory, introduce constructor injection for new code while keeping facades as thin proxies, add test proving isolation.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
