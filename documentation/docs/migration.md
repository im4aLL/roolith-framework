# Migration

Migrations are handled by [roolith/migration](https://github.com/im4aLL/roolith-migration).
It is a simple migration tool for PHP applications.

## Installation

Install it with Composer.

```bash
composer require roolith/migration
```

## Setup

Create a PHP file `migration.php` at your project root and add the following code.

```php
<?php
use Roolith\Migration\Migration;

require __DIR__ . "/vendor/autoload.php";

$migration = new Migration();
$migration
    ->settings([
        "folder" => __DIR__ . "/migrations",
        "database" => [
            "host" => "localhost",
            "name" => "db_name",
            "user" => "user",
            "pass" => "pass",
        ],
    ])
    ->run($argv);
```

Assuming your filename is `migration.php`, you can run the migration commands as follows.

```bash
php migration.php migration:create migration_name
php migration.php migration:run # it will run all pending migrations
php migration.php migration:run migration_name
php migration.php migration:rollback migration_name
php migration.php migration:status
```

## Notes

- It will create the `migrations` table if it does not exist.
- It will create the migrations folder if it does not exist.
- You can change the name of the folder by passing settings.

## Example of Migration File

```bash
php migration.php migration:create create_users_table
```

```php
<?php

use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Migration\Interfaces\MigrationInterface;

class CreateUsersTable implements MigrationInterface
{
    public function up(DatabaseInterface $db): void {}

    public function down(DatabaseInterface $db): void {}
}
```

Inside `up()` and `down()` you get the [database](/database) connection, so every driver method from the [database](/database) page is available on `$db`.
