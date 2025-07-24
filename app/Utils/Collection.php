<?php
namespace App\Utils;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Usage example
 * $collection = Collection::make([1, 2, 3, 4, 5]);
 * $users = Collection::make([
 * ['name' => 'Alice', 'age' => 30],
 * ['name' => 'Bob', 'age' => 25],
 * ['name' => 'Charlie', 'age' => 35]
 * ]);
 *
 * Chain operations
 * $result = $collection
 * ->filter(fn($x) => $x > 2)
 * ->map(fn($x) => $x * 2)
 * ->sum(); // 24
 *
 * $adults = $users->where('age', '>=', 30);
 * $names = $users->pluck('name');
 */
class Collection implements IteratorAggregate, Countable, ArrayAccess
{
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function make(array $items = []): self
    {
        return new self($items);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    public function where(string $key, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $actual = is_array($item) ? $item[$key] : $item->$key;

            return match ($operator) {
                '=' => $actual == $value,
                '!=' => $actual != $value,
                '>' => $actual > $value,
                '>=' => $actual >= $value,
                '<' => $actual < $value,
                '<=' => $actual <= $value,
                default => $actual == $value,
            };
        });
    }

    public function pluck(string $key): self
    {
        return $this->map(fn($item) => is_array($item) ? $item[$key] : $item->$key);
    }

    public function groupBy(string|callable $key): self
    {
        $groups = [];

        foreach ($this->items as $item) {
            $groupKey = is_callable($key) ? $key($item) :
                (is_array($item) ? $item[$key] : $item->$key);

            $groups[$groupKey][] = $item;
        }

        return new self($groups);
    }

    public function sort(callable $callback = null): self
    {
        $items = $this->items;

        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }

        return new self(array_values($items));
    }

    public function sortBy(string|callable $key): self
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($key) {
            $valueA = is_callable($key) ? $key($a) :
                (is_array($a) ? $a[$key] : $a->$key);
            $valueB = is_callable($key) ? $key($b) :
                (is_array($b) ? $b[$key] : $b->$key);

            return $valueA <=> $valueB;
        });

        return new self($items);
    }

    public function first(callable $callback = null): mixed
    {
        if ($callback === null) {
            return $this->items[0] ?? null;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function last(callable $callback = null): mixed
    {
        if ($callback === null) {
            return end($this->items) ?: null;
        }

        $items = array_reverse($this->items);
        foreach ($items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function sum(string|callable $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return $this->pluck($key)->sum();
    }

    public function avg(string|callable $key = null): int|float|null
    {
        $count = $this->count();
        return $count > 0 ? $this->sum($key) / $count : null;
    }

    public function unique(): self
    {
        return new self(array_values(array_unique($this->items, SORT_REGULAR)));
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    public function chunk(int $size): self
    {
        return new self(array_chunk($this->items, $size));
    }

    public function take(int $limit): self
    {
        return new self(array_slice($this->items, 0, $limit));
    }

    public function skip(int $offset): self
    {
        return new self(array_slice($this->items, $offset));
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return json_encode($this->items);
    }

    // IteratorAggregate
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    // Countable
    public function count(): int
    {
        return count($this->items);
    }

    // ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
