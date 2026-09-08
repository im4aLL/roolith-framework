<?php
namespace App\Models;

use App\Core\DatabaseFactory;
use ReflectionClass;
use ReflectionException;
use Roolith\Store\Interfaces\DatabaseInterface;

class Model
{
    /**
     * Table name for this model (must be non-empty before ORM use).
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Primary key column name.
     *
     * @var string
     */
    protected string $primaryColumn = 'id';

    /**
     * Get db instance.
     *
     * @return DatabaseInterface Database connection.
     */
    protected function db(): DatabaseInterface
    {
        return DatabaseFactory::getInstance();
    }

    /**
     * Get the table name.
     *
     * @return string Table name (may be empty when misconfigured).
     */
    protected function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Get primary column.
     *
     * @return string Primary key column name.
     */
    protected function getPrimaryColumn(): string
    {
        return $this->primaryColumn;
    }

    /**
     * Table instance.
     *
     * Asserts a non-empty table so misconfiguration fails fast with context
     * instead of invalid SQL late.
     *
     * @return DatabaseInterface ORM scoped to this model table.
     * @throws \RuntimeException When the table name is empty.
     */
    public function getOrm(): DatabaseInterface
    {
        $table = $this->getTableName();

        if (trim($table) === '') {
            throw new \RuntimeException(
                "Misconfigured model " . static::class . ": table name is empty. Set protected string \$table."
            );
        }

        return $this->db()->table($table);
    }

    /**
     * Get all records.
     *
     * @return array<int, mixed> All rows.
     */
    public function getAll(): array
    {
        return $this->getOrm()->get();
    }

    /**
     * Get called class instance.
     *
     * Throws instead of returning false so callers chaining
     * self::instance()->getAll() get context instead of a fatal on false.
     *
     * @return static Model instance for late static binding.
     * @throws \RuntimeException When reflection fails.
     */
    protected static function instance(): static
    {
        try {
            $reflectionClass = new ReflectionClass(get_called_class());

            /** @var static $instance */
            $instance = $reflectionClass->newInstance();

            return $instance;
        } catch (ReflectionException $e) {
            throw new \RuntimeException(
                "Unable to instantiate model " . get_called_class() . ": " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get all records.
     *
     * @return array<int, mixed> All rows.
     */
    public static function all(): array
    {
        return self::instance()->getAll();
    }

    /**
     * Get orm.
     *
     * @return DatabaseInterface ORM scoped to the model table.
     * @throws \RuntimeException When the table name is empty.
     */
    public static function orm(): DatabaseInterface
    {
        return self::instance()->getOrm();
    }

    /**
     * Get raw database connection.
     *
     * @return DatabaseInterface Raw database connection.
     */
    public static function raw(): DatabaseInterface
    {
        return self::instance()->db();
    }

    /**
     * Get table name.
     *
     * @return string Table name.
     */
    public static function tableName(): string
    {
        return self::instance()->getTableName();
    }
}
