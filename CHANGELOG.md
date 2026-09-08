# Changelog

All notable changes to this project are documented here. Format follows Keep a Changelog (Added, Changed, Fixed).

## Unreleased

### Added

- Phase 5 hardening: `App\Support` helpers (`Debug`, `Url`, `Translator`, `Redirect`, `Html`, `IdGenerator`), `trans()` plus `escape()` globals, CLI-aware `p()`.
- Route safety: `App\Core\RouteValidator`, `php roolith route:list`, callable `[Class, method]` preference.
- Data: `App\Database\Migrator` (`php roolith migrate*`), `DatabaseFactory::transaction()`, model `$fillable` plus `$casts` plus `validate()`.
- Observability: shared `App\Core\Log`, router plus 404 plus controller PSR-3 coverage, `phpstan.neon` level 6, `composer lint` plus `composer analyse`, full CI with `npm build` plus `composer audit`.
- Docs: corrected validator plus request plus middleware plus i18n recipes, frontend workflow, canonical architecture note.

## Previous

- See git history for Phase 0-4: env epic, request plus validation correctness, response pipeline, ops plus frontend safety.
