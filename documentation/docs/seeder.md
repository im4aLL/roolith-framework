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
