# Tickets index

Source: AUDIT.md audit of ARCHITECTURE.md and README.md with code verification.

Total tickets: 67

Status values: Open, In Progress, Done.

## Order

- [x] 001-A1 - Change fail-open dev default to fail-closed production-safe default (Status: Done)
- [ ] 002-A2 - Fix LFI/path traversal via locale cookie (Status: Open)
- [ ] 003-A3 - Validate Host before redirect and URL building to prevent Host header injection and open redirect (Status: Open)
- [ ] 004-A4 - Harden session cookies and session lifecycle (Status: Open)
- [ ] 005-A5 - Harden Storage::setCookie and deleteCookie (Status: Open)
- [ ] 006-A6 - Replace extension-only upload validation with MIME and storage hardening (Status: Open)
- [ ] 007-A7 - Stop trusting proxy IP headers unconditionally (Status: Open)
- [ ] 008-A8 - Add CSRF protection for state-changing routes (Status: Open)
- [ ] 009-A9 - Implement real auth example and deny-by-default middleware (Status: Open)
- [ ] 010-A10 - Block direct access to sensitive files (Status: Open)
- [ ] 011-A11 - Do not leak internal errors to browsers (Status: Open)
- [ ] 012-A12 - Add security headers (Status: Open)
- [ ] 013-A13 - Move rate limiting off session to survive cookie clear and multi-server (Status: Open)
- [ ] 014-A14 - Sanitize output at render, not input at read (Status: Open)
- [ ] 015-B1 - Ensure System::complete() always runs even when code calls exit or die (Status: Open)
- [x] 016-B2 - Fix require_once for routes to allow re-entry and tests (Status: Done)
- [x] 017-B3 - Preserve exception chain and types on bootstrap and DB connect (Status: Done)
- [x] 018-B4 - Fix Rules::required for 0, numbers, and arrays (Status: Done)
- [x] 019-B5 - Harden requiredArray, minLength, maxLength, requiredIf (Status: Done)
- [x] 020-B6 - Fix Request stream and has handling for JSON and falsy values (Status: Done)
- [x] 021-B7 - Make Validator fail loudly on unknown rules and missing fields (Status: Done)
- [x] 022-B8 - Guard Model::instance and empty table configuration (Status: Done)
- [ ] 023-B9 - Fix LazyLoad uninitialized state and loose key matching (Status: Open)
- [ ] 024-B10 - Fix SessionRateLimiter prune-on-block bug and key collision (Status: Open)
- [x] 025-B11 - Replace time() version with content hash (Status: Done)
- [x] 026-B12 - Make timezone and locale configurable (Status: Done)
- [x] 027-B13 - Normalize url() slash handling and error path (Status: Done)
- [ ] 028-B14 - Return proper API responses with headers and status (Status: Open)
- [ ] 029-B15 - Fix Controller::view error contract (Status: Open)
- [x] 030-B16 - Harden filesystem helpers against deletion mistakes (Status: Done)
- [x] 031-B17 - Fix parseBasicTemplate regex injection (Status: Done)
- [ ] 032-B18 - Replace insecure ID generators with random_bytes (Status: Open)
- [ ] 033-B19 - Fix Vite helper HMR edge case (Status: Open)
- [x] 034-C1 - Reduce static facades with explicit seams and resettable singletons (Status: Done)
- [x] 035-C2 - Unify environment handling between Config package and app helpers (Status: Done)
- [ ] 036-C3 - Introduce a real Request and Response pipeline (Status: Open)
- [ ] 037-C4 - Replace CMS constant includes and installer.zip with explicit package or env flag (Status: Open)
- [ ] 038-C5 - Namespace global helpers and avoid __ collision (Status: Open)
- [x] 039-C6 - Validate config shape at bootstrap (Status: Done)
- [ ] 040-C7 - Document string-based Controller at method dispatch with safety checks (Status: Open)
- [ ] 041-C8 - Clarify cache and event usage because they are shipped but unwired (Status: Open)
- [x] 042-D1 - Do not force debugMode(false) globally (Status: Done)
- [ ] 043-D2 - Add migration, seeder, and transaction story to core instead of docs-only (Status: Open)
- [ ] 044-D3 - Define model mass-assignment and type rules (Status: Open)
- [ ] 045-D4 - Optimize LazyLoad from O(n*m) filter to keyed map (Status: Open)
- [ ] 046-E1 - Stop wiping assets blindly and separate uploads from build output (Status: Open)
- [x] 047-E2 - Version CSS and JS consistently (Status: Done)
- [ ] 048-E3 - Document Vite proxy limits (Status: Open)
- [x] 049-F1 - Add first-class .env support and stop putting secrets in config.php (Status: Done)
- [x] 050-F2 - Pin PHP and extensions consistently (Status: Done)
- [ ] 051-F3 - Fix Docker credentials drift and add healthchecks (Status: Open)
- [ ] 052-F4 - Do not bind-mount entire repo in a prod-like compose without note (Status: Open)
- [ ] 053-F5 - Remove or replace installer.zip binary in git (Status: Open)
- [ ] 054-F6 - Add Apache hardening for prod (Status: Open)
- [x] 055-G1 - Add PHP test harness and first regression tests (Status: Done)
- [x] 056-G2 - Add PSR-3 logging and use it on all error paths (Status: Done)
- [ ] 057-G3 - Add static analysis and style checks (Status: Open)
- [x] 058-G4 - Add CI for app code, not just docs FTP (Status: Done)
- [ ] 059-G5 - Standardize 404 and error pages with correct status codes (Status: Open)
- [ ] 060-G6 - Tighten Composer version constraints deliberately (Status: Open)
- [ ] 061-H1 - Fix README typos and broken paths (Status: Open)
- [ ] 062-H2 - Keep single source for architecture to avoid drift (Status: Open)
- [ ] 063-H3 - Correct contradictory validator example (Status: Open)
- [ ] 064-H4 - Document request helper nuances and file upload flow end to end (Status: Open)
- [ ] 065-H5 - Document middleware attachment, rate limiter reset, and i18n fallback (Status: Open)
- [ ] 066-H6 - Add missing repo hygiene files (Status: Open)
- [ ] 067-H7 - Clarify frontend workflow commands (Status: Open)

## Execution order (replaces numeric order)

Numeric IDs 001-067 are stable lookup IDs only. Do not execute in numeric order. Execute by phases below. Each phase leaves the project runnable with `composer test` plus `curl` checks passing.

### Phase 0 - Foundations (run first)

Goal: make the project testable, fail-closed, and observable before security fixes land.

- 055-G1 minimal: `phpunit/phpunit`, `phpunit.xml`, `tests/RulesTest.php` with `required` data provider, wire `composer test`. Full suites later.
- 035-C2 plus 049-F1 plus 001-A1 as one env epic: single `APP_ENV` with `production` default, `.env` loader, `config/config.php` maps `$_ENV` with defaults, bridge `ROOLITH_ENV` constant for BC, Whoops only when `APP_ENV=development`, generic 500 otherwise. Block 001 on 035 plus 049 design decision.
- 039-C6: `ConfigValidator` asserts `baseUrl`, `database|null`, `version`, `forceNonWww` at bootstrap.
- 056-G2 skeleton: PSR-3 logger with trace ID wired to bootstrap and exception handler only.
- 050-F2: require `php>=8.2`, add `intl,zip,opcache` to `Dockerfile`, document `composer check-platform-reqs`.
- 058-G4 minimal: `ci.yml` running `composer install`, `composer test`, `php -l` on push.

Verify: `composer install && composer test` passes; unset `APP_ENV` boots with `display_errors=0` and generic 500, `APP_ENV=development` shows Whoops; missing `baseUrl` throws helpful validator message; logs show bootstrap entries with trace ID.

Deferred: full validator suites, full logger coverage, static analysis 057-G3, CSRF, upload hardening, Response migration, Vite hashing.

### Phase 1 - Security fail-closed slice

- 002-A2 LFI allowlist, 003-A3 Host validation, 010-A10 `.htaccess` deny, 004-A4 plus 005-A5 batched cookie hardening, 011-A11 plus 059-G5 batched error pages with correct codes, 007-A7 trusted proxies, 012-A12 baseline headers.

Verify: `curl -b 'lang=../../etc/passwd'` falls back to `en`; `curl -H 'Host: evil.com' -i` does not redirect to evil host; `curl -i /config/config.php /vendor/autoload.php /.git/HEAD /installer.zip /constant.php` all 404; `curl -I` shows security headers; forced exception in prod returns 500 with trace ID not stack.

Deferred: 006 upload MIME (Phase 3), 008 CSRF (blocked on Phase 3), 009 auth example, 013 driver migration, installer removal.

### Phase 2 - Request, validation, and persistence correctness

- 016-B2 `require` plus interface check plus 034-C1a `resetForTests()`. Then 020-B6 Request JSON plus `has` fix. Then 018-B4 plus 021-B7 batched, then 019-B5 alone. Then 017-B3, 022-B8, 042-D1, 030-B16, 027-B13, 031-B17, 026-B12. Then 025-B11 plus 047-E2 batched versioning.

Verify: `composer test` covers `Rules`, `Validator` unknown-rule throw, `Request::has("0")===true`, JSON body, `LazyLoad` guards; asset URL stable in prod.

Deferred: Response migration, CSRF, auth, LazyLoad perf pass, rate limiter fixes, docs examples 063-H3 and 064-H4 blocked until this phase is done.

### Phase 3 - Response pipeline plus dependent security

- 015-B1 plus 036-C3a `Response($body,$status,$headers)` plus shutdown fallback. Then 036-C3b middleware `process(request,next)` plus 028-B14 `json()` helper plus 029-B15 `Controller::view` contract. Then 008-A8 CSRF, then 009-A9 auth example. Then 023-B9 then 045-D4 sequenced, 024-B10 then 013-A13 sequenced (split 013 into doc now and driver later), 006-A6a MIME plus names then 006-A6b storage plus deny.

Verify: routes re-entry works in same process; `System::complete()` runs after redirect; POST without CSRF token rejected; API returns JSON with correct status; `shell.php.jpg` rejected with random stored name.

Deferred: Docker creds unification, CMS decoupling, installer removal, Vite proxy docs.

### Phase 4 - Ops and frontend safety

- 051-F3 `.env` creds plus healthcheck, 052-F4 dev vs prod compose, 054-F6 Apache hardening, 046-E1 build to `assets/build` first then 047-E2 plus 048-E3 plus 033-B19 batch, 037-C4 CMS-only plus 053-F5 installer removal merged.

Verify: `docker compose up` waits healthy from `.env` only; `npm run build` preserves uploads fixture; `curl -i /installer.zip` 404 with release asset plus checksum documented.

### Phase 5 - Hardening, testing, docs (last)

- Code: 038-C5, 040-C7, 041-C8, 043-D2, 044-D3, 014-A14 (needs view escaping audit), 032-B18, 060-G6.
- Tooling: 056-G2 full coverage, 057-G3 phpstan level 6, 058-G4 full CI with `npm build` plus `composer audit`.
- Docs batch 1 anytime: 061-H1, 062-H2, 066-H6. Docs batch 2 blocked on code: 063-H3, 064-H4, 065-H5. Frontend docs 067-H7 after Phase 4.

## Dependencies and sequencing (Blocked by)

- 001-A1 blocked by 035-C2 plus 049-F1. Execute as one env epic.
- 008-A8 blocked by 036-C3. Phase 1 token helper only if needed early, full middleware check after Phase 3.
- 011-A11 plus 059-G5 plus 028-B14 plus 029-B15 blocked by 056-G2 skeleton plus 036-C3a plus 015-B1.
- 024-B10 before 013-A13. Same file `SessionRateLimiter.php:29-45`.
- 023-B9 before 045-D4. Same function `LazyLoad.php:94-107`, reuse normalized string keys.
- 018-B4 plus 021-B7 together, then 019-B5. Block 063-H3 plus 064-H4 on 018 plus 019 plus 021.
- 004-A4 plus 005-A5 batch. Shared cookie config plus `Storage.php:19-33,53-62`.
- 025-B11 plus 047-E2 batch. Single cache-busting decision.
- 037-C4 plus 053-F5 merge. Keep 053 as removal execution, reduce 037 to CMS flag only.
- 063-H3 plus 064-H4 blocked on Phase 2. 065-H5 blocked on Phase 3 (middleware plus rate limiter plus i18n).

## Split and batch notes (no new IDs, use a/b commits in same ticket)

- Split: 036-C3 into 036a Response plus `json()` and 036b middleware migration; 055-G1 into 055a minimal harness and 055b remaining suites; 034-C1 into 034a `resetForTests()` and 034b injection; 006-A6 into 006a MIME plus names and 006b storage plus deny; 013-A13 into 013a document limits and 013b driver; 015-B1 into exit removal and shutdown fallback.
- Batch: docs-batch-1 (061 plus 062 plus 066), docs-batch-2 (063 plus 064 plus 065, blocked), frontend-batch (047 plus 048 plus 033 after 046), small-utils-batch (026 plus 027 plus 031 plus 032), composer-batch (050 plus 060).

## Verification per ticket (replaces generic acceptance until ticket is rewritten)

- 002: `phpunit tests/LanguageTest.php` with `../../` payload resolves to `en`; `curl -b 'lang=../../x'` renders English.
- 001: unset `APP_ENV` shows generic 500 without Whoops paths; `APP_ENV=development` shows Whoops.
- 003: `curl -H 'Host: evil.com' -i` does not redirect to evil host and logs mismatch.
- 010: `curl -i` matrix for `/config/config.php`, `/vendor/autoload.php`, `/.git/HEAD`, `/installer.zip`, `/constant.php` all 404.
- 006: `shell.php.jpg` rejected, valid jpg with matching `finfo` MIME accepted with `[0-9a-f]{16}` name, direct curl to stored path denied.
- 018: data-provider asserts `required` passes for `"0"`, `0`, `[0]` and fails for `null`, `""`, `[]`.
- 020: POST JSON `{"a":0}` asserts `Request::has('a')===true` and `php://input` read once.
- 024: time-mocked test asserts blocked bucket prunes after window and persists on blocked path.
- 011 plus 059: missing route returns 404 with `views/404.php` body; forced exception returns 500 with trace ID in body and full trace in log.

## Risk and BC watch list

- 014-A14: stored data now unescaped, views must escape. Rollback: revert commit.
- 021-B7: unknown rule now throws `InvalidArgumentException`, may break existing forms. Rollback: revert commit.
- 015-B1 plus 036-C3: `redirect()` returns `Response` instead of `exit`, `Controller::view: string|bool` becomes `string` or `Response`. Rollback: revert commit.
- 038-C5: `__` to `trans()` alias change. Keep thin globals for BC.
- 050-F2: PHP `>=8.2` drops 8.0 and 8.1. Rollback: revert `composer.json` plus `Dockerfile`.
- 025-B11 plus 047-E2: asset URLs change to hashed names. Clear `assets/build` on rollback.
- 010-A10: deny rules may 404 legitimate paths. Verify matrix before merge.

## Suggested fix order from AUDIT.md (kept for reference)

1. Security fail-closed defaults, LFI allowlist, Host validation, error leakage, upload MIME checks.

2. Reliability exits and shutdown cleanup, validator and request JSON fixes, model guards, version hash.

3. Config env unification plus .env support, Docker creds and healthcheck, PHP version pin.

4. Test harness plus logging plus CI, then Response pipeline and factory resets.

5. Docs typos, architecture single source, README examples, repo hygiene files.
