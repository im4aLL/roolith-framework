# Ticket H7 - Clarify frontend workflow commands

Status: Open

Order: 67 of 67

Category: Docs

Severity: Low

Source: AUDIT.md item H7

## Problem

`README.md:60-85` lists `npm run dev`, `watch`, `build` but `package.json` scripts not cross-checked here and `source/scss/app.scss` vs `source/js/app.js` entry naming differs from `vite.config.mjs:8-11` input keys `app` and `style`.

## Location

`README.md:60-85`, `vite.config.mjs:8-20`, `package.json`

## Suggested fix

Paste actual `package.json` scripts, explain `dev` proxy URL vs direct `:8080`, note where built files land.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
