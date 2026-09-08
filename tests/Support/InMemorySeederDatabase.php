<?php
namespace Tests\Support;

use LogicException;
use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Paginate;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

/**
 * In-memory Database double for package-backed migration tests.
 *
 * Stores status-table rows (id, name, file_type, status) and honors
 * where() filtering plus id ordering so the package run/status commands
 * behave like they do against MySQL. Raw query() calls (SHOW COLUMNS,
 * SHOW INDEX during package bootstrap) explicitly return no rows.
 * Query-builder methods the package never calls (orWhere, delete) fail
 * loudly instead of returning false-green values.
 */
class InMemorySeederDatabase implements DatabaseInterface
{
    /**
     * Stored rows keyed by table name.
     *
     * @var array<string, array<int, object>>
     */
    public array $tables = [];

    /**
     * Next auto-increment id per table.
     *
     * @var array<string, int>
     */
    public array $nextIds = [];

    /**
     * Currently selected table.
     *
     * @var string
     */
    private string $table = '';

    /**
     * Active where conditions.
     *
     * @var array<int, array{column: string, value: mixed}>
     */
    private array $wheres = [];

    /**
     * Raw SQL from the last query() call, if any.
     *
     * @var string|null
     */
    private ?string $rawQuery = null;

    /**
     * Recorded execute queries.
     *
     * @var array<int, string>
     */
    public array $executed = [];

    /**
     * Establish database connection.
     *
     * @param mixed $config Connection config.
     * @return bool Always true.
     */
    public function connect($config): bool
    {
        return true;
    }

    /**
     * Disconnect from a database.
     *
     * @return bool Always true.
     */
    public function disconnect(): bool
    {
        return true;
    }

    /**
     * Reset all states.
     *
     * @return DatabaseInterface Self.
     */
    public function reset(): DatabaseInterface
    {
        return $this;
    }

    /**
     * Return filtered records for the current table.
     *
     * Raw SHOW queries (package bootstrap checks) always yield no rows.
     * Builder rows sort by numeric id ascending, matching the package
     * orderBy id ASC expectation.
     *
     * @return array<int, object> Matching rows.
     */
    public function get(): array
    {
        if ($this->rawQuery !== null) {
            $this->rawQuery = null;

            return [];
        }

        $rows = $this->tables[$this->table] ?? [];
        $out = [];

        foreach ($rows as $row) {
            $match = true;

            foreach ($this->wheres as $where) {
                $column = $where['column'];
                $value = $where['value'];

                if (!isset($row->{$column}) || $row->{$column} != $value) {
                    $match = false;

                    break;
                }
            }

            if ($match) {
                $out[] = $row;
            }
        }

        usort($out, static function ($a, $b): int {
            $aId = isset($a->id) && is_numeric($a->id) ? (int) $a->id : 0;
            $bId = isset($b->id) && is_numeric($b->id) ? (int) $b->id : 0;

            return $aId <=> $bId;
        });

        return $out;
    }

    /**
     * Return first item of records.
     *
     * @return false|object First row or false.
     */
    public function first(): object|bool
    {
        $rows = $this->get();

        return $rows[0] ?? false;
    }

    /**
     * Get total count of a result.
     *
     * @return int Row count.
     */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Add where condition to an existing query.
     *
     * @param mixed $name Column name.
     * @param mixed $value Bound value or operator.
     * @param mixed $expression Operator or bound value.
     * @return DatabaseInterface Self.
     */
    public function where($name, $value, $expression = '='): DatabaseInterface
    {
        $this->wheres[] = ['column' => (string) $name, 'value' => $value];

        return $this;
    }

    /**
     * Unsupported OR condition.
     *
     * Fails loudly so tests never get false-green AND behavior.
     *
     * @param mixed $name Column name.
     * @param mixed $value Bound value or operator.
     * @param mixed $expression Operator or bound value.
     * @return DatabaseInterface Never returns.
     * @throws LogicException Always.
     */
    public function orWhere($name, $value, $expression = '='): DatabaseInterface
    {
        throw new LogicException('InMemorySeederDatabase does not support orWhere().');
    }

    /**
     * Get data by id.
     *
     * @param mixed $id Record id.
     * @return object|false Always false.
     */
    public function find($id): object|bool
    {
        return false;
    }

    /**
     * Retrieve an array of items.
     *
     * @param mixed $nameArray Columns.
     * @return array<int, mixed> Always empty.
     */
    public function pluck($nameArray): array
    {
        return [];
    }

    /**
     * Set ORDER BY for the next select/get.
     *
     * Ordering is fixed to id ascending in get(), matching package use.
     *
     * @param string $column Column name.
     * @param string $direction ASC or DESC.
     * @return DatabaseInterface Self.
     */
    public function orderBy(string $column, string $direction = 'ASC'): DatabaseInterface
    {
        return $this;
    }

    /**
     * Set LIMIT/OFFSET for the next select/get.
     *
     * @param int $limit Row limit.
     * @param int $offset Row offset.
     * @return DatabaseInterface Self.
     */
    public function limit(int $limit, int $offset = 0): DatabaseInterface
    {
        return $this;
    }

    /**
     * Set OFFSET for the next select/get.
     *
     * @param int $offset Row offset.
     * @return DatabaseInterface Self.
     */
    public function offset(int $offset): DatabaseInterface
    {
        return $this;
    }

    /**
     * Pagination stub.
     *
     * @param array<string, mixed> $array Pagination params.
     * @return PaginatorInterface Empty paginator.
     */
    public function paginate(array $array): PaginatorInterface
    {
        return new Paginate($array);
    }

    /**
     * Record a raw query.
     *
     * The following get(), first(), or count() answers it explicitly
     * (SHOW introspection yields no rows) without touching table state.
     *
     * @param mixed $string Query string.
     * @param mixed $method Query method.
     * @param mixed $bindings Bound values.
     * @return DatabaseInterface Self.
     */
    public function query($string, $method = null, $bindings = []): DatabaseInterface
    {
        $this->rawQuery = is_string($string) ? $string : '';

        return $this;
    }

    /**
     * Database raw execute.
     *
     * @param string $query Query string.
     * @param array<string, mixed> $bindings Bound values.
     * @return mixed Always true.
     */
    public function execute(string $query, array $bindings = []): mixed
    {
        $this->executed[] = $query;

        return true;
    }

    /**
     * Set table name.
     *
     * @param mixed $name Table name.
     * @return DatabaseInterface Self.
     */
    public function table($name): DatabaseInterface
    {
        $this->table = (string) $name;
        $this->wheres = [];
        $this->rawQuery = null;

        if (!isset($this->tables[$this->table])) {
            $this->tables[$this->table] = [];
            $this->nextIds[$this->table] = 1;
        }

        return $this;
    }

    /**
     * Database select query stub.
     *
     * @param mixed $array Select spec.
     * @param mixed $bindings Bound values.
     * @return DatabaseInterface Self.
     */
    public function select($array, $bindings = []): DatabaseInterface
    {
        return $this;
    }

    /**
     * Insert query storing status rows in memory.
     *
     * @param mixed $array Row data.
     * @param array<int, string> $uniqueArray Unique columns.
     * @return InsertResponse Insert result.
     */
    public function insert($array, array $uniqueArray = []): InsertResponse
    {
        $table = $this->table;

        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
            $this->nextIds[$table] = 1;
        }

        $row = is_array($array) ? $array : [];
        $row['id'] = $this->nextIds[$table]++;
        $row['status'] = $row['status'] ?? 'pending';
        $this->tables[$table][] = (object) $row;

        return new InsertResponse(['affectedRow' => 1, 'insertedId' => $row['id']]);
    }

    /**
     * Update rows matching the where clause.
     *
     * @param mixed $array Row data.
     * @param array<string, mixed> $whereArray Where clause.
     * @param array<int, string> $uniqueArray Unique columns.
     * @return UpdateResponse Update result.
     */
    public function update($array, array $whereArray, array $uniqueArray = []): UpdateResponse
    {
        $affected = 0;
        $data = is_array($array) ? $array : [];
        $where = is_array($whereArray) ? $whereArray : [];

        foreach ($this->tables[$this->table] ?? [] as $row) {
            $match = true;

            foreach ($where as $column => $value) {
                if (!isset($row->{$column}) || $row->{$column} != $value) {
                    $match = false;

                    break;
                }
            }

            if ($match) {
                foreach ($data as $column => $value) {
                    $row->{$column} = $value;
                }

                $affected++;
            }
        }

        return new UpdateResponse(['affectedRow' => $affected]);
    }

    /**
     * Unsupported delete.
     *
     * Fails loudly so tests never get false-green behavior.
     *
     * @param mixed $whereArray Where clause.
     * @return DeleteResponse Never returns.
     * @throws LogicException Always.
     */
    public function delete($whereArray): DeleteResponse
    {
        throw new LogicException('InMemorySeederDatabase does not support delete().');
    }

    /**
     * Turn on debug mode.
     *
     * @param bool $mode Debug flag.
     * @return DatabaseInterface Self.
     */
    public function debugMode(bool $mode = true): DatabaseInterface
    {
        return $this;
    }

    /**
     * Whether a transaction is active.
     *
     * @return bool Always false.
     */
    public function inTransaction(): bool
    {
        return false;
    }

    /**
     * Begin transaction stub.
     *
     * @return bool Always true.
     */
    public function beginTransaction(): bool
    {
        return true;
    }

    /**
     * Commit transaction stub.
     *
     * @return bool Always true.
     */
    public function commit(): bool
    {
        return true;
    }

    /**
     * Roll back transaction stub.
     *
     * @return bool Always true.
     */
    public function rollBack(): bool
    {
        return true;
    }

    /**
     * Run callback inside a transaction.
     *
     * @param callable $callback Work receiving this instance.
     * @return mixed Callback return value.
     */
    public function transaction(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Get collected debug queries.
     *
     * @return array<int, array{query:string, bindings:mixed}> Always empty.
     */
    public function getDebugLog(): array
    {
        return [];
    }

    /**
     * Clear collected debug queries.
     *
     * @return DatabaseInterface Self.
     */
    public function clearDebugLog(): DatabaseInterface
    {
        return $this;
    }
}
