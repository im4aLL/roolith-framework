# CLI

All console commands run through the single `roolith` entry point from the project root. Framework commands (`route:list`, `migrate`, `seed`, `help`) are handled by `App\Console\Cli`; anything else falls through to the [Generator](/generator).

```bash
php roolith help
php roolith --help # same output, -h works too
```

Every command returns exit code `0` on success and `1` on failure without side effects for unknown commands, so `route:list` can gate CI.

## generate

Scaffold classes from templates in `app/Core/generator-templates`. See [Generator](/generator) for template format, shortcuts (`g c`), and custom commands.

```bash
php roolith generate controller DemoController
php roolith generate model Product
php roolith generate middleware AuthMiddleware
```

## route:list

Print the route table and lint handler references with `class_exists` plus `method_exists`. Exits `1` when any handler is invalid. Prefer the callable form `[WelcomeController::class, 'index']`; the legacy `Class@"method"` string still runs but is flagged in style guidance.

```bash
php roolith route:list
```

```text
+--------+---------+------------------------+----------------------------------------------+
| Method | Path    | Name                   | Action                                       |
+--------+---------+------------------------+----------------------------------------------+
| GET    | /       | -                      | Closure                                      |
| GET    | /example| -                      | App\Controllers\WelcomeController@index      |
```

## migrate

Run pending migrations, inspect state, scaffold new ones, or roll one back. Powered by `roolith/migration` through `App\Database\MigrationFactory` (files in `database/migrations` as `*.migration.php`, rows in the shared `migrations` table). Requires a configured database (see [Configuration](/configuration)); without one the command reports the connection failure instead of running.

```bash
php roolith migrate # run all pending migrations
php roolith migrate:run # same as migrate
php roolith migrate:run CreateUsersTable # run one migration (short or full name)
php roolith migrate:status # show migration rows and statuses
php roolith migrate:status CreateUsersTable # status of one migration (short or full name)
php roolith migrate:create CreateUsersTable # scaffold database/migrations/<timestamp>_<rand>_CreateUsersTable.migration.php
php roolith migrate:rollback CreateUsersTable # roll back one completed migration (name required)
```

Native `migration:run`, `migration:status`, `migration:create`, and `migration:rollback` work too; `migrate`, `migrate:run`, `migrate:status`, `migrate:create`, and `migrate:rollback` are BC aliases. Extra arguments are rejected with usage plus exit `1`. See [Migration](/migration) for the migration class format (`up()` / `down()` receiving the shared connection). Note: rollback reverts a single named migration; the old internal runner batch rollback no longer exists.

## seed

Fill the database with test or reference data. Powered by `roolith/migration` through `App\Database\MigrationFactory` (files in `database/seeders` as `*.seeder.php`, rows in the shared `migrations` table with `file_type = 'seeder'`). `seed` runs all pending seeders (or one when named); `seed:run` optionally targets one. `seeder:create`, `seeder:run`, and `seeder:status` are aliases of their `seed:*` forms, and native `seeder:*` commands work too. Extra arguments are rejected with usage plus exit `1`.

```bash
php roolith seed # run all pending seeders
php roolith seed:status # show seeder rows and statuses
php roolith seed:create UserSeeder # scaffold database/seeders/<timestamp>_<rand>_UserSeeder.seeder.php
php roolith seed:run # run all pending (same as seed)
php roolith seed:run UserSeeder # run one seeder (short or full name)
```

See [Seeder](/seeder) for the seeder class format. There is no seeder rollback.

## help

```bash
php roolith help
php roolith --help
php roolith -h
```

```text
Roolith CLI
  php roolith generate <type> <Name>
  php roolith route:list
  php roolith migrate
  php roolith migrate:status [Name]
  php roolith migrate:create <Name>
  php roolith migrate:rollback <Name>
  php roolith seed
  php roolith seed:status [Name]
  php roolith seed:create <Name>
  php roolith seed:run [Name]
  (migration:* and seeder:* natives accepted)
```
