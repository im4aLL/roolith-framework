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

Run pending migrations, inspect state, scaffold new ones, or roll back. Requires a configured database (see [Configuration](/configuration)); without one the command reports the connection failure instead of running.

```bash
php roolith migrate # run all pending migrations
php roolith migrate:status # show applied vs pending counts and names
php roolith migrate:create CreateUsersTable # scaffold database/migrations/<timestamp>_CreateUsersTable.php
php roolith migrate:rollback # roll back the last batch
php roolith migrate:rollback CreateUsersTable # roll back a single migration
```

See [Migration](/migration) for the migration class format (`up()` / `down()` receiving the shared connection) and batch behavior.

## seed

Fill the database with test or reference data. `seed` runs all pending seeders; `seed:run` optionally targets one. `seeder:create` and `seeder:run` are aliases of `seed:create` and `seed:run`.

```bash
php roolith seed # run all pending seeders
php roolith seed:status # show applied vs pending seeders
php roolith seed:create UserSeeder # scaffold database/seeders/<timestamp>_UserSeeder.php
php roolith seed:run # run all pending (same as seed)
php roolith seed:run UserSeeder # run one seeder (already-applied reruns when named explicitly)
```

See [Seeder](/seeder) for the seeder class format and already-applied no-op behavior.

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
  php roolith migrate:status
  php roolith migrate:create <Name>
  php roolith migrate:rollback [Name]
  php roolith seed
  php roolith seed:status
  php roolith seed:create <Name>
  php roolith seed:run [Name]
```
