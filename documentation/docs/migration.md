# Migration

Migrations are handled by the internal `App\Database\Migrator` (no external package required).
Migration files live in `database/migrations` and applied names are tracked in the `migrations` table so schema never drifts between deploys.

## Commands

Run from the project root:

```bash
php roolith migrate:create create_users_table
php roolith migrate
php roolith migrate:status
php roolith migrate:rollback
php roolith migrate:rollback create_users_table
```

- `migrate:create <Name>` scaffolds `database/migrations/<timestamp>_<rand>_<Slug>.php` with an `up()` plus `down()` skeleton. The directory is created when missing and the name includes a random suffix so concurrent creates never collide. An empty name after sanitizing defaults to `migration`; creation retries with a numeric suffix up to 100 attempts for a unique name and throws `RuntimeException` when the directory or file cannot be written, when `random_bytes()` fails, or when no unique name is found.
- `migrate` runs pending migrations in filename order. Each `up()` runs inside the injected connection transaction and the tracking row is written only on success.
- `migrate:status` lists applied plus pending names without running anything.
- `migrate:rollback` reverts the last batch in reverse order (or one named migration when given). Each `down()` runs inside a transaction.

## Migration files

```bash
php roolith migrate:create create_users_table
```

```php
<?php

use App\Database\MigrationInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class CreateUsersTable implements MigrationInterface
{
    /**
     * Apply the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function up(DatabaseInterface $db): void
    {
    }

    /**
     * Revert the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function down(DatabaseInterface $db): void
    {
    }
}
```

Inside `up()` and `down()` you get the [database](/database) connection, so every driver method from the [database](/database) page is available on `$db`.

## Complete example

Here is a complete migration file that creates a `users` table.

```php
<?php

use App\Database\MigrationInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class CreateUsersTable implements MigrationInterface
{
    /**
     * Apply the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function up(DatabaseInterface $db): void
    {
        $db->execute("
            CREATE TABLE IF NOT EXISTS `users` (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                last_logged_in DATETIME NULL,
                verification_code VARCHAR(100) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );
        ");
    }

    /**
     * Revert the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function down(DatabaseInterface $db): void
    {
        $db->execute("DROP TABLE IF EXISTS `users`;");
    }
}
```

## Model-to-table contract

One model class maps to one table via `protected string $table` plus `$primaryColumn` (default `id`). The migrator creates those tables; the model reads and writes them. See [models](/models) for `$fillable`, `$casts`, and `validate()`.

## Notes

- The `migrations` table (`migration` VARCHAR primary, `batch` INT, `migrated_at` TIMESTAMP) is created automatically when missing.
- Only `*.php` files in `database/migrations` run, sorted by name so timestamp prefixes order correctly.
- Read failures log via `App\Core\Log::error()` and rethrow so a failed read never looks like an empty success.
- Migration names are restricted to letters, digits, and underscores; anything else throws `InvalidArgumentException` before touching the filesystem, while a missing file, missing class, or class not implementing `MigrationInterface` throws `RuntimeException`.
- The class name is derived by stripping a leading timestamp prefix (`digits` plus underscore), splitting on underscores, and StudlyCasing the parts.
- Batch helpers `nextBatch()`, `latestBatch()`, and `migrationsInBatch()` group applied rows by batch; batch and applied-list read failures log via `App\Core\Log::error()` and rethrow.
- `Migrator` is injectable via `__construct(?string $dir = null, ?DatabaseInterface $db = null)` with `DEFAULT_DIR = 'database/migrations'`, `TABLE = 'migrations'`, and `directory()` exposing the active dir.

## Seeding data

Migrations own schema; rows belong in [seeders](/seeder) (`php roolith seed:create`, `php roolith seed`, `php roolith seed:status`, `php roolith seed:run`). Migrate first, then seed.
