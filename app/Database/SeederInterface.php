<?php
namespace App\Database;

use Roolith\Store\Interfaces\DatabaseInterface;

/**
 * Seeder contract for database seed data.
 *
 * Each seeder file class implements run() to insert seed rows. It
 * receives the shared Database connection so every driver method
 * (execute, query, table) is available.
 */
interface SeederInterface
{
    /**
     * Run the seeder.
     *
     * @param DatabaseInterface $db Shared database connection.
     * @return void
     */
    public function run(DatabaseInterface $db): void;
}
