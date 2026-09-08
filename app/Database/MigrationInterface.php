<?php
namespace App\Database;

/**
 * Migration contract for database schema changes.
 *
 * BC alias of `Roolith\Migration\Interfaces\MigrationInterface` so files
 * written against the old internal runner keep loading under the
 * `roolith/migration` package (which resolves instances via
 * `is_subclass_of`). New files should import the vendor interface directly.
 */
interface MigrationInterface extends \Roolith\Migration\Interfaces\MigrationInterface
{
}
