# Changelog

All notable changes to this project are documented here. Format follows Keep a Changelog (Added, Changed, Fixed).

## Unreleased

### Added

- Hardening: `App\Support` helpers (`Debug`, `Url`, `Translator`, `Redirect`, `Html`, `IdGenerator`), `trans()` plus `escape()` globals, CLI-aware `p()`.
- Route safety: `App\Core\RouteValidator`, `php roolith route:list`, callable `[Class, method]` preference.
- Data: `DatabaseFactory::transaction()`, model `$fillable` plus `$casts` plus `validate()`.
- Data: replaced the internal migrator/seeder with `roolith/migration ^2.0` via `App\Database\MigrationFactory` (shared `migrations` status table, `database/migrations/*.migration.php`, `database/seeders/*.seeder.php`); `App\Database\MigrationInterface` plus `SeederInterface` are now BC aliases of the vendor contracts; `migrate:rollback` requires a name (batch rollback removed); native `migration:*` plus `seeder:status` commands also work through `php roolith`.
- Observability: shared `App\Core\Log`, router plus 404 plus controller PSR-3 coverage, `phpstan.neon` level 6, `composer lint` plus `composer analyse`, full CI with `npm build` plus `composer audit`.
- Docs: corrected validator plus request plus middleware plus i18n recipes, frontend workflow, canonical architecture note.

### Removed

- Data: deleted `App\Database\Migrator` plus `App\Database\Seeder` (replaced by `roolith/migration ^2.0` via `App\Database\MigrationFactory`; `MigrationInterface` plus `SeederInterface` stay as BC aliases). `migrate:rollback` now requires a name (batch rollback removed), and only `database/migrations/*.migration.php` plus `database/seeders/*.seeder.php` run (old bare `*.php` files are orphaned).

## Previous

- See git history for earlier work: env epic, request plus validation correctness, response pipeline, ops plus frontend safety.
