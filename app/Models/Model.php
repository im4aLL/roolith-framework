<?php
namespace App\Models;

use App\Core\DatabaseFactory;
use ReflectionClass;
use ReflectionException;
use Roolith\Store\Interfaces\DatabaseInterface;

class Model
{
    protected $table = '';
    protected $primaryColumn = 'id';

    /**
     * Get db instance
     *
     * @return DatabaseInterface
     */
    protected function db()
    {
        return DatabaseFactory::getInstance();
    }

    /**
     * Get the table name
     *
     * @return string
     */
    protected function getTableName()
    {
        return $this->table;
    }

    /**
     * Get primary column
     *
     * @return string
     */
    protected function getPrimaryColumn()
    {
        return $this->primaryColumn;
    }

    /**
     * Table instance
     *
     * @return DatabaseInterface
     */
    public function getOrm()
    {
        return $this->db()->table($this->getTableName());
    }

    /**
     * Get all records
     *
     * @return iterable
     */
    public function getAll()
    {
        return $this->getOrm()->get();
    }

    /**
     * Get called class instance
     *
     * @return Model
     * @throws ReflectionException
     */
    protected static function instance()
    {
        $reflectionClass = new ReflectionClass(get_called_class());

        return $reflectionClass->newInstance();
    }

    /**
     * Get all records
     *
     * @return iterable
     * @throws ReflectionException
     */
    public static function all()
    {
        return self::instance()->getAll();
    }

    /**
     * Get orm
     *
     * @return DatabaseInterface
     * @throws ReflectionException
     */
    public static function orm()
    {
        return self::instance()->getOrm();
    }
}
