<?php
namespace App\Models;

use App\Core\DatabaseFactory;
use App\Core\Interfaces\ValidatorRulesInterface;
use ReflectionClass;
use ReflectionException;
use Roolith\Store\Interfaces\DatabaseInterface;

/**
 * Base model binding one class to one database table.
 *
 * Subclasses set protected string $table plus optional $primaryColumn,
 * $fillable for mass-assignment filtering, $casts for read-time type
 * coercion, and validationRules() for write validation. Reads go
 * through getAll()/all() with castRows(), writes filter fillable plus
 * validate(), and multi-write paths use transaction().
 */
class Model
{
    /**
     * Table name for this model (must be non-empty before ORM use).
     *
     * One model maps to one table; the migrator creates this table
     * and the ORM reads plus writes it. See documentation/docs/models.md
     * for the full model-to-table contract.
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
     * Mass-assignable columns for validated writes.
     *
     * Empty means all columns are allowed (BC for read-only models).
     * Set to an explicit list (for example ['name', 'email']) so
     * filterFillable() drops unexpected keys like is_admin before
     * insert or update. Always combine with validate().
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Type casts applied when reading rows.
     *
     * Map column to cast (int, float, bool, string, datetime). Reads
     * via all() plus find helpers run through castRow() so numeric
     * strings from PDO become native types.
     *
     * @var array<string, string>
     */
    protected array $casts = [];

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
     * Reads via the model ORM then runs rows through castRows() so
     * $casts (int, bool, and similar) apply. Non-array rows (for
     * example stdClass from the driver) pass through untouched via
     * the castRows() guard.
     *
     * @return array<int, mixed> All rows with casts applied.
     */
    public function getAll(): array
    {
        $rows = $this->getOrm()->get();

        return $this->castRows($rows);
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
     * Delegates to getAll() so $casts apply via castRows().
     *
     * @return array<int, mixed> All rows with casts applied.
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

    /**
     * Get the fillable columns for this model.
     *
     * @return array<int, string> Allowed mass-assignment columns.
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Get the type casts for this model.
     *
     * @return array<string, string> Column to cast map.
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Filter input to fillable columns only.
     *
     * When fillable is empty every key passes (BC). Otherwise only
     * listed keys survive so role or is_admin style fields cannot be
     * mass-assigned. Call before every insert or update.
     *
     * @param array<string, mixed> $data Raw input keyed by column.
     * @return array<string, mixed> Filtered data with only fillable keys.
     */
    public function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }

        $allowed = array_fill_keys($this->fillable, true);
        $filtered = [];

        foreach ($data as $key => $value) {
            if (isset($allowed[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Cast one row to native types per $casts.
     *
     * Supports int, float, bool, string, and datetime (Y-m-d H:i:s
     * string passthrough). Unknown cast names leave the value as-is.
     * Null stays null so nullable columns keep their meaning.
     *
     * @param array<string, mixed> $row Raw row keyed by column.
     * @return array<string, mixed> Cast row.
     */
    public function castRow(array $row): array
    {
        if ($this->casts === []) {
            return $row;
        }

        foreach ($this->casts as $column => $cast) {
            if (!array_key_exists($column, $row) || $row[$column] === null) {
                continue;
            }

            $value = $row[$column];
            $kind = strtolower(trim((string) $cast));

            if ($kind === 'int' || $kind === 'integer') {
                $row[$column] = (int) $value;
            } elseif ($kind === 'float' || $kind === 'double') {
                $row[$column] = (float) $value;
            } elseif ($kind === 'bool' || $kind === 'boolean') {
                $row[$column] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
            } elseif ($kind === 'string') {
                $row[$column] = (string) $value;
            } elseif ($kind === 'datetime') {
                $row[$column] = (string) $value;
            }
        }

        return $row;
    }

    /**
     * Cast many rows per $casts.
     *
     * @param array<int, array<string, mixed>> $rows Raw rows.
     * @return array<int, array<string, mixed>> Cast rows.
     */
    public function castRows(array $rows): array
    {
        $casted = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $casted[] = $this->castRow($row);
            } else {
                $casted[] = $row;
            }
        }

        return $casted;
    }

    /**
     * Validation rules for write paths.
     *
     * Override in subclasses to return Validator-compatible rules
     * (for example ['email' => Rules::set()->isEmail()->isRequired()]).
     * Empty means no validation; validated writes via createValidated()
     * then skip the check but still filter fillable.
     *
     * @return array<string, mixed> Rules per field.
     */
    protected function validationRules(): array
    {
        return [];
    }

    /**
     * Validate input against validationRules().
     *
     * Runs App\Core\Validator::check() when rules exist. Every rule
     * value must implement ValidatorRulesInterface (for example
     * Rules::set()->isEmail()); anything else throws
     * InvalidArgumentException so misconfigured models fail fast.
     * Returns the errors map (field to failing rules); empty means
     * valid.
     *
     * @param array<string, mixed> $data Input keyed by field.
     * @return array<string, mixed> Errors grouped by field, empty when valid.
     * @throws \InvalidArgumentException When a rule is not a ValidatorRulesInterface instance.
     */
    public function validate(array $data): array
    {
        $rules = $this->validationRules();

        if ($rules === []) {
            return [];
        }

        foreach ($rules as $field => $rule) {
            if (!$rule instanceof ValidatorRulesInterface) {
                throw new \InvalidArgumentException(
                    "Invalid validation rule for field '{$field}' in " . static::class . ": expected instance of " . ValidatorRulesInterface::class . ", got " . get_debug_type($rule) . "."
                );
            }
        }

        $validator = new \App\Core\Validator();
        $validator->check($data, $rules);
        $errors = $validator->errors();

        if ($errors instanceof \Traversable) {
            /** @var array<string, mixed> $traversed */
            $traversed = iterator_to_array($errors);

            return $traversed;
        }

        /** @var array<string, mixed> $asArray */
        $asArray = (array) $errors;

        return $asArray;
    }

    /**
     * Run a callback inside a transaction on this model connection.
     *
     * Thin proxy over DatabaseFactory::transaction() so writes stay
     * atomic: the callback receives the shared Database instance,
     * commits on return, rolls back and rethrows on failure.
     *
     * @param callable $callback Work to run transactionally.
     * @return mixed Callback return value.
     */
    public static function transaction(callable $callback): mixed
    {
        return DatabaseFactory::transaction($callback);
    }
}
