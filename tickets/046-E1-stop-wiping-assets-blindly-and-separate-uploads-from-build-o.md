# Ticket E1 - Stop wiping assets blindly and separate uploads from build output

Status: Done

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

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4: vite.config.mjs outDir assets/build with emptyOutDir true (only wipes build output), helpers read assets/build/.vite/manifest.json first with legacy fallback and return assets/build/ prefix, views use assets/build/css plus js, .gitignore ignores only /assets/build, public/uploads fixture (.keep-me.txt plus README) plus assets/README document separation. Verified: npm run build preserves public/uploads/.keep-me.txt and emits hashed js plus css plus manifest, composer test 178 OK.

Review follow-up: vite.config.mjs now sets publicDir:false so public/uploads/* is never copied into assets/build/uploads/* (build output confirmed clean), and new public/uploads/.htaccess disables the PHP engine, removes script handlers, and denies script extensions plus dotfiles. Verified: npm run build preserves the fixture with no duplication, htaccess pattern matrix checked in PHP, composer test green.
