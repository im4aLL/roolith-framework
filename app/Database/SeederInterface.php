<?php
namespace App\Database;

/**
 * Seeder contract for database seed data.
 *
 * BC alias of `Roolith\Migration\Interfaces\SeederInterface` so files
 * written against the old internal runner keep loading under the
 * `roolith/migration` package. New files should import the vendor interface directly.
 */
interface SeederInterface extends \Roolith\Migration\Interfaces\SeederInterface
{
}
