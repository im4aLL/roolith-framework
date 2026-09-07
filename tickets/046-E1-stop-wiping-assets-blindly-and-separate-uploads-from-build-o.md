# Ticket E1 - Stop wiping assets blindly and separate uploads from build output

Status: Open

Order: 46 of 67

Category: Frontend

Severity: Medium

Source: AUDIT.md item E1

## Problem

`vite.config.mjs:53-56` sets `emptyOutDir:true` with `outDir:assets`, so any user uploads under `assets/` vanish on build, and `.gitignore:4` ignores `/assets/` so builds are not reviewed.

## Location

`vite.config.mjs:53-56`, `.gitignore:4`

## Suggested fix

Build to `assets/build` or guard upload dirs, document that uploads must live in `public/uploads` or storage outside `assets`, verify build preserves media.

## Acceptance criteria

- [ ] Fix implemented as described or documented alternative.

- [ ] Manual or automated verification note added here.

- [ ] No unrelated scope changed.

## Notes

Update Status to In Progress when started and to Done when verified.
