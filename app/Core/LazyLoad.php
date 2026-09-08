<?php
namespace App\Core;

use App\Models\Model;
use App\Utils\Arr;
use Throwable;
use Traversable;

/**
 * Eager-load helper attaching related models to parent rows.
 *
 * Queues with() descriptors then materializes relations in get() via a
 * single keyed index (O(n+m)). Unknown models fail closed per row set, and
 * Traversable inputs are materialized once so generators are never consumed
 * by emptiness probes.
 */
class LazyLoad
{
    /**
     * Original input data.
     *
     * @var iterable<int|string, object>
     */
    private iterable $data = [];

    /**
     * Working result set with relations attached.
     *
     * @var iterable<int|string, object>
     */
    private iterable $result = [];

    /**
     * Queued eager-load descriptors (init to [] so get() without with() is safe).
     *
     * @var array<int, object>
     */
    private array $loadArray = [];

    /**
     * Whether to also attach the full match array under key_array.
     *
     * @var bool
     */
    private bool $isAddArrayResult = false;

    /**
     * Raw settings map.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * Create a lazy loader for a result set.
     *
     * Traversable inputs (including generators) are materialized once via
     * iterator_to_array so later emptiness checks plus relation loops can
     * iterate repeatedly without losing the first element (M5).
     *
     * @param iterable<int|string, object> $data Parent rows to attach relations to.
     * @param array<string, mixed> $settings Optional flags (add_array).
     */
    public function __construct(iterable $data, array $settings = [])
    {
        if ($data instanceof Traversable) {
            try {
                $data = iterator_to_array($data);
            } catch (Throwable) {
                $data = [];
            }
        }

        $this->data = $data;
        $this->result = $data;

        if (isset($settings['add_array'])) {
            $this->isAddArrayResult = (bool) $settings['add_array'];
        }

        $this->settings = $settings;
    }

    /**
     * Add items load array
     *
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return $this
     */
    public function with(string $model, string $foreignKey, string $localKey = 'id'): static
    {
        $this->loadArray[] = Arr::arrayToObject([
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey,
        ]);

        return $this;
    }

    /**
     * Attach data and returns new result
     *
     * Empty load queue or empty parent set returns early without warnings.
     * Traversable results are materialized once before probing so generators
     * stay intact.
     *
     * @return iterable<int|string, object> Result set with relations attached.
     */
    public function get(): iterable
    {
        $this->materializeResult();

        if ($this->loadArray === []) {
            return $this->result;
        }

        if ($this->isEmptyResult()) {
            return $this->result;
        }

        foreach ($this->loadArray as $item) {
            $this->attachModelData($item);
        }

        return $this->result;
    }

    /**
     * Check whether the parent result set is empty without consuming iterators.
     *
     * Traversable results are materialized once instead of probing with
     * foreach, which would discard the first element of a Generator (M5).
     *
     * @return bool True when there are no parent rows to attach to.
     */
    private function isEmptyResult(): bool
    {
        if (is_array($this->result)) {
            return $this->result === [];
        }

        if ($this->result instanceof \Countable) {
            return count($this->result) === 0;
        }

        if ($this->result instanceof Traversable) {
            $this->materializeResult();

            return is_array($this->result) ? $this->result === [] : false;
        }

        return false;
    }

    /**
     * Materialize Traversable results once so generators can be re-iterated.
     *
     * Converts data plus result from Traversable to array in place. Failures
     * fall back to an empty array so get() stays total.
     *
     * @return void
     */
    private function materializeResult(): void
    {
        if ($this->result instanceof Traversable) {
            try {
                $this->result = iterator_to_array($this->result);
            } catch (Throwable) {
                $this->result = [];
            }
        }

        if ($this->data instanceof Traversable) {
            try {
                $this->data = iterator_to_array($this->data);
            } catch (Throwable) {
                $this->data = [];
            }
        }
    }

    /**
     * Load additional data and inject into result
     *
     * Guards missing keys via null coalescing, normalizes IDs to string so
     * int 1 matches string "1" deliberately, handles empty data early, then
     * attaches via a keyed map indexed once (O(n+m) instead of
     * O(n*m) filter per parent). Unknown or non-model classes plus ORM
     * failures fail closed per descriptor (M4) so one bad with() never
     * breaks the whole result set.
     *
     * @param object $dto Descriptor with model, foreignKey, and localKey strings.
     * @return void
     */
    private function attachModelData(object $dto): void
    {
        $model = (string) ($dto->model ?? '');
        $foreignKey = (string) ($dto->foreignKey ?? '');
        $localKey = (string) ($dto->localKey ?? 'id');

        if ($model === '' || $foreignKey === '' || $localKey === '') {
            return;
        }

        if (!class_exists($model) || !is_a($model, Model::class, true)) {
            return;
        }

        $parts = explode('\\', $model);
        $last = end($parts);

        if (!is_string($last) || $last === '') {
            return;
        }

        $key = Arr::pascalCaseToSnakeCase($last);
        $ids = [];

        foreach ($this->result as $item) {
            $item->{$key} = null;

            if ($this->isAddArrayResult) {
                $item->{$key . '_array'} = null;
            }

            $foreignValue = $item->{$foreignKey} ?? null;

            if ($foreignValue === null || $foreignValue === '') {
                continue;
            }

            $ids[] = (string) $foreignValue;
        }

        $uniqueIds = array_values(array_unique($ids));

        if (count($uniqueIds) === 0) {
            return;
        }

        try {
            $modelInstance = new ($model)();

            if (!$modelInstance instanceof Model) {
                return;
            }

            $data = $modelInstance::orm()->where($localKey, 'IN', array_values($uniqueIds))->get();
        } catch (Throwable) {
            return;
        }

        if ($data === null || $data === false) {
            return;
        }

        try {
            $rows = is_array($data) ? $data : ($data instanceof Traversable ? iterator_to_array($data) : []);
        } catch (Throwable) {
            return;
        }

        if (count($rows) === 0) {
            return;
        }

        $indexed = self::indexByStringKey($rows, $localKey);

        if ($indexed === []) {
            return;
        }

        foreach ($this->result as $item) {
            $foreignValue = $item->{$foreignKey} ?? null;

            if ($foreignValue === null || $foreignValue === '') {
                continue;
            }

            $matches = $indexed[(string) $foreignValue] ?? [];

            if ($matches === []) {
                continue;
            }

            $item->{$key} = count($matches) === 1 ? $matches[0] : $matches;

            if ($this->isAddArrayResult) {
                $item->{$key . '_array'} = $matches;
            }
        }
    }

    /**
     * Index related rows by their normalized string local key once.
     *
     * Rows missing the key (null or empty string) are skipped. IDs are cast
     * to string so int and string forms collide deliberately.
     *
     * @param array<int|string, object> $rows Related rows to index.
     * @param string $localKey Property name holding the join key.
     * @return array<string, array<int, object>> Key to matching rows map.
     */
    private static function indexByStringKey(array $rows, string $localKey): array
    {
        /** @var array<string, array<int, object>> $indexed */
        $indexed = [];

        foreach ($rows as $row) {
            if (!is_object($row)) {
                continue;
            }

            $value = $row->{$localKey} ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $indexed[(string) $value][] = $row;
        }

        return $indexed;
    }
}
