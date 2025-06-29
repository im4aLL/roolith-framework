<?php
namespace App\Models;

use App\Core\DatabaseFactory;
use ReflectionClass;
use ReflectionException;
use Roolith\Store\Interfaces\DatabaseInterface;

class Model
{
    protected string $table = '';
    protected string $primaryColumn = 'id';

    /**
     * Get db instance
     *
     * @return DatabaseInterface
     */
    protected function db(): DatabaseInterface
    {
        return DatabaseFactory::getInstance();
    }

    /**
     * Get the table name
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Get primary column
     *
     * @return string
     */
    protected function getPrimaryColumn(): string
    {
        return $this->primaryColumn;
    }

    /**
     * Table instance
     *
     * @return DatabaseInterface
     */
    public function getOrm(): DatabaseInterface
    {
        return $this->db()->table($this->getTableName());
    }

    /**
     * Get all records
     *
     * @return iterable
     */
    public function getAll(): iterable
    {
        return $this->getOrm()->get();
    }

    /**
     * Get called class instance
     *
     * @return Model|false
     */
    protected static function instance(): Model|false
    {
        try {
            $reflectionClass = new ReflectionClass(get_called_class());
            return $reflectionClass->newInstance();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * Get all records
     *
     * @return iterable
     */
    public static function all(): iterable
    {
        return self::instance()->getAll();
    }

    /**
     * Get orm
     *
     * @return DatabaseInterface
     */
    public static function orm(): DatabaseInterface
    {
        return self::instance()->getOrm();
    }

    /**
     * @return DatabaseInterface
     */
    public static function raw(): DatabaseInterface
    {
        return self::instance()->db();
    }

    /**
     * Get Table name
     *
     * @return string
     */
    public static function tableName(): string
    {
        return self::instance()->getTableName();
    }
}
