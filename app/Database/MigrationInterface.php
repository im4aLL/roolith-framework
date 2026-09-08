<?php
namespace App\Database;

use Roolith\Store\Interfaces\DatabaseInterface;

/**
 * Migration contract for database schema changes.
 *
 * Each migration file class implements up() to apply the change and
 * down() to revert it. Both receive the shared Database connection
 * so every driver method (execute, query, table) is available.
 */
interface MigrationInterface
{
    /**
     * Apply the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function up(DatabaseInterface $db): void;

    /**
     * Revert the migration.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function down(DatabaseInterface $db): void;
}
