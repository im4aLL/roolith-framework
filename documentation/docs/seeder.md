# Seeder

Seeders are handled by the internal `App\Database\Seeder` (no external package required). Seeder files live in `database/seeders` and applied names are tracked in the `seeds` table so seed data never duplicates between deploys.

## Commands

Run from the project root:

```bash
php roolith seed:create add_users
php roolith seed
php roolith seed:status
php roolith seed:run
php roolith seed:run add_users
```

- `seed:create <Name>` scaffolds `database/seeders/<timestamp>_<rand>_<Slug>.php` with a `run()` skeleton. The directory is created when missing and the name includes a random suffix so concurrent creates never collide. `seeder:create` is an accepted alias.
- `seed` runs pending seeders in filename order. Each `run()` runs inside the injected connection transaction and the tracking row is written only on success.
- `seed:run [Name]` runs all pending seeders, or one named seeder when given. `seeder:run` is an accepted alias with the same behavior.
- `seed:status` lists applied plus pending names without running anything.

## Seeder files

```bash
php roolith seed:create add_users
```

```php
<?php

use App\Database\SeederInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class AddUsers implements SeederInterface
{
    /**
     * Run the seeder.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function run(DatabaseInterface $db): void
    {
    }
}
```

Inside `run()` you get the [database](/database) connection, so every driver method from the [database](/database) page is available on `$db`.

## Complete example

Here is a complete seeder file that inserts dummy users into the `users` table created in the [migration example](/migration#complete-example).

```php
<?php

use App\Database\SeederInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class UserData implements SeederInterface
{
    public function run(DatabaseInterface $db): void
    {
        $db->execute("
            INSERT INTO users (name, email, role, last_logged_in)
            VALUES
              ('John Doe',                'john.doe@example.com',           'developer', '2025-11-01 09:15:00'),
              ('Bob Smith',               'bob.smith@example.com',          'qa',        '2025-11-02 10:30:00'),
              ('Charlie Nguyen',          'charlie.nguyen@example.com',     'developer', '2025-11-03 11:45:00'),
              ('Diana Patel',             'diana.patel@example.com',        'dba',       '2025-11-04 12:00:00'),
              ('Ethan Clark',             'ethan.clark@example.com',        'developer', '2025-11-05 13:10:00'),
              ('Fatima Ali',              'fatima.ali@example.com',         'qa',        '2025-11-06 14:20:00'),
              ('George Brown',            'george.brown@example.com',       'developer', '2025-11-07 15:35:00'),
              ('Hannah Wilson',           'hannah.wilson@example.com',      'qa',        '2025-11-08 16:40:00'),
              ('Imran Qureshi',           'imran.qureshi@example.com',      'dba',       '2025-11-09 17:55:00'),
              ('Jessica Thompson',        'jessica.thompson@example.com',   'developer', '2025-11-10 18:05:00');
        ");
    }
}
```

Seeders pair with [migrations](/migration): migrate the schema first, then seed the rows.

## Notes

- The `seeds` table (`seed` VARCHAR primary, `batch` INT, `seeded_at` TIMESTAMP) is created automatically when missing.
- Only `*.php` files in `database/seeders` run, sorted by name so timestamp prefixes order correctly.
- Read failures log via `App\Core\Log::error()` and rethrow so a failed read never looks like an empty success.
- Seeder names are restricted to letters, digits, and underscores; anything else is rejected before touching the filesystem.

## Legacy alternative

Older docs used the external [roolith/migration](https://github.com/im4aLL/roolith-migration) package via a standalone `migration.php` script (`php migration.php seeder:create seed_name`, `php migration.php seeder:run`, `php migration.php seeder:run seed_name` with `Roolith\Migration\Interfaces\SeederInterface`). That path still works for existing projects, but new code should use the internal `php roolith seed` commands above.
