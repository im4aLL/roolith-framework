# Seeder

Seeders are handled by the same [roolith/migration](https://github.com/im4aLL/roolith-migration) package used for [migrations](/migration).
Set up `migration.php` as described there, then use the seeder commands below.

## Seeder Commands

Assuming your filename is `migration.php`, you can run the seeder commands as follows.

```bash
php migration.php seeder:create seed_name
php migration.php seeder:run # it will run all pending seeds
php migration.php seeder:run seed_name
```

## Example of Seed File

```bash
php migration.php seeder:create add_users
```

```php
<?php

use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Migration\Interfaces\SeederInterface;

class AddUsers implements SeederInterface
{
    public function run(DatabaseInterface $db): void {}
}
```

Inside `run()` you get the [database](/database) connection, so every driver method from the [database](/database) page is available on `$db`.

## Complete Example

Here is a complete seeder file that inserts dummy users into the `users` table created in the [migration example](/migration#complete-example).

```php
<?php

use Roolith\Migration\Interfaces\SeederInterface;
use Roolith\Store\Interfaces\DatabaseInterface;

class _1764469509_UserData implements SeederInterface
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
