# Ticket F5 - Remove or replace installer.zip binary in git

Status: Done

Order: 53 of 67

Category: Ops

Severity: Medium

Source: AUDIT.md item F5

## Problem

Root `installer.zip` is opaque, bloats clones, cannot review.

## Location

`/installer.zip` (repo root), `ARCHITECTURE.md:189`

## Suggested fix

Publish as release asset with checksum or composer package, add script to fetch on demand, remove from repo history if sensitive.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Notes

Done Phase 4 merged with 037-C4: git rm installer.zip (231K, 128 files, SHA256 8f9dba0725e509a321002c6db0a41086f6f64717d83b3017a669938659bcd45b documented in docs/cms-installer.md as last-tracked reference), .gitignore has /installer.zip, .gitattributes keeps /installer.zip export-ignore, composer.json archive.exclude covers installer.zip plus sha256, .htaccess already 404s installer.zip (extended SecurityHardeningTest asserts it), docs/cms-installer.md documents gh release upload plus download plus shasum verify plus unzip plus APP_ENABLE_CMS=1. Verified: git ls-files shows not tracked, git archive HEAD has no root installer.zip, composer archive has no installer.zip, .htaccess regex maps /installer.zip to 404, composer test 178 OK.

Review follow-up: .gitattributes adds /installer.zip.sha256 export-ignore, .dockerignore plus .gitignore add installer.zip.sha256 lines to match composer.json archive.exclude. Verified: git ls-files shows no installer.zip blob, git archive HEAD has no root installer.zip, composer test green.

Owner decision Sep 2026: kept tracked per owner decision, excluded from create-project via composer.json archive.exclude + .gitattributes export-ignore, verified with composer archive + git archive. installer.zip restored via git restore (tracked, not ignored - .gitignore entries removed as contradictory), .dockerignore keeps installer.zip entries for lean build context only. Verified: git status shows no D installer.zip, git ls-files shows tracked, git archive HEAD has no installer.zip, composer archive zip has no installer.zip, composer test 180 OK.

Final re-review Sep 2026 (tracked-but-excluded truth): docs/cms-installer.md plus ARCHITECTURE.md:189 plus docs/architecture.md plus docs/getting-started.md reworded from never-in-git to tracked-locally plus excluded-from-dist via archive.exclude plus export-ignore plus dockerignore plus .htaccess 404; composer.json archive.exclude extended for parity with .gitattributes (.github, documentation, tests); installer.zip.sha256 generated via shasum -a 256 and tracked (8f9dba0725e509a321002c6db0a41086f6f64717d83b3017a669938659bcd45b, verified with shasum -c); SecurityHardeningTest comment reworded to tracked binary stays hidden over HTTP. Verified: git ls-files shows installer.zip plus installer.zip.sha256, git archive HEAD has no installer.zip via export-ignore, composer archive zip has no installer.zip plus no .github plus no documentation plus no tests, composer validate clean, composer test 180 OK.

Right-pane re-review Sep 2026: .htaccess RedirectMatch extended to installer.zip(\.sha256)? so the checksum file also 404s (SecurityHardeningTest asserts installer.zip plus installer.zip(\.sha256)?, php regex matrix shows /installer.zip plus /installer.zip.sha256 plus case variants BLOCKED and .bak plus zipx plus / plus /example allowed); docker healthcheck quoted to -p"$$MYSQL_ROOT_PASSWORD" in docker-compose.yml plus docker-compose.prod.yml (docker compose config OK for dev plus prod, quoted single-arg vs unquoted split verified via sh); composer vs git tests/.gitkeep parity documented as intentional in docs/cms-installer.md (git keeps .gitkeep to preserve empty dir, dist omits tests entirely) plus Why-404s section now lists both files. Prior mediums verified already correct (.env.example:63 tracked-locally wording, routing.md:252 guarded cms-routes require) - no regression. Verified: composer test 180 OK (586 assertions, 1 pre-existing deprecation), php -l clean, composer validate clean (pre-existing exact-version warnings only), docker compose config quiet OK.

Owner follow-up Sep 2026: installer.zip.sha256 sidecar deleted per owner (rm worktree, was untracked - git ls-files shows only installer.zip tracked; checksum published as release asset only, no sidecar tracked). composer.json archive.exclude plus .gitattributes plus .dockerignore reverted to installer.zip only; docs/cms-installer.md release flow uses shasum -a 256 installer.zip on demand without -c file plus verify block checks installer.zip absence only; .htaccess reverted to installer\.zip only (SecurityHardeningTest back to 6 assertions). Checksum on demand via shasum -a 256 installer.zip.
