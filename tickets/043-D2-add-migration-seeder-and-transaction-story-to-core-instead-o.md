# Ticket D2 - Add migration, seeder, and transaction story to core instead of docs-only

Status: Done

Order: 43 of 67

Category: Data

Severity: Medium

Source: AUDIT.md item D2

## Problem

`ARCHITECTURE.md:160-161` lists migrations and Cycle ORM as documented patterns, but default app has no runner, so schema drifts.

## Location

`ARCHITECTURE.md:160-161`, `documentation/*`

## Suggested fix

Add minimal `php roolith migrate` or adopt existing tool, add `DB::transaction(fn)` helper, document model-to-table contract.

## Acceptance criteria

- [x] Fix implemented as described or documented alternative.

- [x] Manual or automated verification note added here.

- [x] No unrelated scope changed.

## Verification (Phase 5, 2026-09-08)

- Added App\Database\Migrator plus MigrationInterface (database/migrations, migrations table, create/run/status/rollback), php roolith migrate* CLI, DatabaseFactory::transaction() plus Model::transaction() seams, model-to-table contract docs. Verified: Phase5Test migrator create pass, manual migrate:status tested.

## Verification (Seeder story, 2026-09-08)

- Added App\Database\SeederInterface plus App\Database\Seeder (database/seeders, seeds table, create/files/status/run with transaction per seed, InvalidArgumentException slug plus traversal guards, fail-closed applied reads), php roolith seed plus seed:status plus seed:create plus seed:run plus seeder:create plus seeder:run CLI, database/seeders/.gitkeep, internal-first seeder.md docs with legacy roolith/migration demotion, ARCHITECTURE.md seeder line, migration.md seeder cross-link. Verified: tests/SeederTest.php (9 tests) green, composer test green, composer analyse clean, php roolith help lists seed lines, php roolith seed:status returns graceful message without fatal.

## Notes

Update Status to In Progress when started and to Done when verified.
