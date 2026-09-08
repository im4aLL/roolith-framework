<?php
namespace App\Utils;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Array utility helpers (canonical replacement for App\Utils\_).
 *
 * The "_" class name is deprecated since PHP 8.4 (using "_" as a class name).
 * Use App\Utils\Arr instead. App\Utils\_ remains as a BC alias via class_alias
 * in app/Utils/_.php so existing call sites keep working without a deprecation.
 */
class Arr
{
    /**
     * Is associative array
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool True when at least one key is a string.
     */
    public static function isAssociativeArray(array $array): bool
    {
        return count(array_filter(array_keys($array), "is_string")) > 0;
    }

    /**
     * Get only a selected array
     *
     * @param array<mixed> $items Source array.
     * @param array<mixed>|string|int $only Keys or values to keep.
     * @return array<mixed> Filtered array.
     */
    public static function only(array $items, array|string|int $only): array
    {
        $resultArray = [];

        if (is_array($only)) {
            if (self::isAssociativeArray($items)) {
                foreach ($items as $itemKey => $itemValue) {
                    if (in_array($itemKey, $only)) {
                        $resultArray[$itemKey] = $itemValue;
                    }
                }
            } else {
                foreach ($items as $item) {
                    if (in_array($item, $only)) {
                        $resultArray[] = $item;
                    }
                }
            }
        } else {
            if (isset($items[$only])) {
                $resultArray[] = $items[$only];
            }
        }

        return $resultArray;
    }

    /**
     * Exclude items from an array
     *
     * @param array<mixed> $items Source array.
     * @param array<mixed>|string|int $except Keys to exclude.
     * @return array<mixed> Filtered array.
     */
    public static function except(array $items, array|string|int $except): array
    {
        $resultArray = [];

        if (is_array($except)) {
            if (self::isAssociativeArray($items)) {
                foreach ($items as $itemKey => $itemValue) {
                    if (!in_array($itemKey, $except)) {
                        $resultArray[$itemKey] = $itemValue;
                    }
                }
            } else {
                foreach ($items as $itemKey => $itemValue) {
                    if (!in_array($itemKey, $except)) {
                        $resultArray[$itemKey] = $itemValue;
                    }
                }
            }
        } else {
            foreach ($items as $itemKey => $itemValue) {
                if ($itemKey !== $except) {
                    $resultArray[$itemKey] = $itemValue;
                }
            }
        }

        return $resultArray;
    }

    /**
     * Except where it removes by value
     *
     * @param array<mixed> $array Source array.
     * @param array<mixed> $values Values to remove.
     * @return array<mixed> Filtered array.
     */
    public static function exceptByValue(array $array, array $values): array
    {
        return array_filter($array, fn($v) => !in_array($v, $values, true));
    }

    /**
     * Array chunk
     * Creates an array of elements split into groups the length of size. If array can't be split evenly, the final chunk will be the remaining elements.
     *
     * @param array<mixed> $array Source array.
     * @param int $amount Chunk length.
     * @param bool $preserveKey Whether to preserve keys.
     * @return array<int, array<mixed>> List of chunks.
     */
    public static function chunk(array $array, int $amount, bool $preserveKey = false): array
    {
        return array_chunk($array, $amount, $preserveKey);
    }

    /**
     * Compact
     * Creates an array with all false values removed. The values are false, null, 0, "", undefined, and NaN are false.
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Array without falsy values.
     */
    public static function compact(array $array): array
    {
        return array_filter($array, fn($value) => (bool) $value);
    }

    /**
     * Concat
     * Creates a new array concatenating array with any additional arrays and/or values.
     *
     * @param mixed ...$args Arrays and/or values to concatenate.
     * @return array<mixed> Concatenated array.
     */
    public static function concat(mixed ...$args): array
    {
        $result = [];

        foreach ($args as $argument) {
            if (is_array($argument)) {
                foreach ($argument as $i) {
                    $result[] = $i;
                }
            } else {
                $result[] = $argument;
            }
        }

        return $result;
    }

    /**
     * Difference
     *
     * @param array<mixed> ...$args Arrays to compare.
     * @return array<mixed> Values in the first array not present in the others.
     */
    public static function difference(array ...$args): array
    {
        if ($args === []) {
            return [];
        }

        return array_diff(...$args);
    }

    /**
     * Drop
     * Creates a slice of array with n elements dropped from the beginning.
     *
     * @param array<mixed> $array Source array.
     * @param int $n Number of elements to drop.
     * @return array<mixed> Remaining slice.
     */
    public static function drop(array $array, int $n = 1): array
    {
        return array_slice($array, $n);
    }

    /**
     * Drop right
     * Creates a slice of array with n elements dropped from the end.
     *
     * @param array<mixed> $array Source array.
     * @param int $n Number of elements to drop from the end.
     * @return array<mixed> Remaining slice.
     */
    public static function dropRight(array $array, int $n = 1): array
    {
        return array_slice($array, 0, count($array) - $n);
    }

    /**
     * Drop while
     * Creates a slice of array excluding elements dropped from the beginning. Elements are dropped until predicate returns falsey.
     * The predicate is invoked with three arguments: (value, index, array).
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed): mixed $callback Predicate invoked with each value.
     * @return array<mixed> Remaining elements.
     */
    public static function dropWhile(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $n) {
            $isValid = call_user_func($callback, $n);

            if (!$isValid) {
                $result[] = $n;
            }
        }

        return $result;
    }

    /**
     * Filter
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed): mixed $callback Predicate invoked with each value.
     * @return array<mixed> Elements where predicate returned truthy.
     */
    public static function filter(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $n) {
            $isValid = call_user_func($callback, $n);

            if ($isValid) {
                $result[] = $n;
            }
        }

        return $result;
    }

    /**
     * Remove
     * Removes all elements from array that predicate returns truthy for and returns an array of the removed elements.
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed): mixed $callback Predicate invoked with each value.
     * @return array<mixed> Removed elements.
     */
    public static function remove(array $array, callable $callback): array
    {
        return self::dropWhile($array, $callback);
    }

    /**
     * Find index
     *
     * @param array<mixed> $array Source array.
     * @param mixed $callback Predicate callback or value to search for.
     * @return int|string|false Index of the match, or -1 when a callable finds nothing, or false when array_search misses.
     */
    public static function findIndex(array $array, mixed $callback): int|string|false
    {
        if (is_callable($callback)) {
            foreach ($array as $n) {
                $isValid = call_user_func($callback, $n);

                if ($isValid) {
                    return self::indexOf($array, $n);
                }
            }
        } else {
            return self::indexOf($array, $callback);
        }

        return -1;
    }

    /**
     * Index of
     * Gets the index at which the first occurrence of value is found in array
     *
     * @param array<mixed> $array Source array.
     * @param mixed $n Value to search for.
     * @return int|string|false Key of the match, or false when not found.
     */
    public static function indexOf(array $array, mixed $n): int|string|false
    {
        return array_search($n, $array);
    }

    /**
     * Join
     * Converts all elements in an array into a string separated by separator.
     *
     * @param array<mixed> $array Source array.
     * @param string $separator Separator between elements.
     * @return string Joined string.
     */
    public static function join(array $array, string $separator = ","): string
    {
        return implode($separator, $array);
    }

    /**
     * last
     * Gets the last element of array.
     *
     * @param array<mixed> $array Source array.
     * @return mixed Last element, or null when the array is empty.
     */
    public static function last(array $array): mixed
    {
        if ($array === []) {
            return null;
        }

        return $array[count($array) - 1];
    }

    /**
     * First
     *
     * @param array<mixed> $array Source array.
     * @return mixed First element, or null when the array is empty.
     */
    public static function first(array $array): mixed
    {
        if ($array === []) {
            return null;
        }

        return array_values($array)[0];
    }

    /**
     * Array reverse
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Reversed array.
     */
    public static function reverse(array $array): array
    {
        return array_reverse($array);
    }

    /**
     * Take
     * Creates a slice of array with n elements taken from the beginning.
     *
     * @param array<mixed> $array Source array.
     * @param int $n Number of elements to take.
     * @return array<mixed> Taken slice.
     */
    public static function take(array $array, int $n): array
    {
        return array_slice($array, 0, $n);
    }

    /**
     * Take right
     * Creates a slice of array with n elements taken from the end.
     *
     * @param array<mixed> $array Source array.
     * @param int $n Number of elements to take from the end.
     * @return array<mixed> Taken slice.
     */
    public static function takeRight(array $array, int $n): array
    {
        $length = count($array);

        return array_slice($array, $length - $n, $length);
    }

    /**
     * Uniq
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Array with duplicate values removed.
     */
    public static function uniq(array $array): array
    {
        return array_unique($array);
    }

    /**
     * Find
     * Iterates over elements of a collection, returning the first element predicate returns truthy for
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed): mixed $callback Predicate invoked with each value.
     * @param bool $withKey Whether to return the match with its key.
     * @return mixed Matched value, [key => value] when $withKey is true, or false when nothing matches.
     */
    public static function find(array $array, callable $callback, bool $withKey = false): mixed
    {
        foreach ($array as $k => $n) {
            $isValid = call_user_func($callback, $n);

            if ($isValid) {
                if ($withKey) {
                    return [$k => $n];
                }

                return $n;
            }
        }

        return false;
    }

    /**
     * Each
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed, mixed): void $callback Callback invoked with (value, key).
     * @return void
     */
    public static function each(array $array, callable $callback): void
    {
        foreach ($array as $itemKey => $item) {
            call_user_func($callback, $item, $itemKey);
        }
    }

    /**
     * Contains
     *
     * @param array<mixed> $array Source array.
     * @param mixed $n Value to search for.
     * @return bool True when the value is present.
     */
    public static function contains(array $array, mixed $n): bool
    {
        foreach ($array as $key => $value) {
            if ($value === $n) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map
     *
     * @param array<mixed> $array Source array.
     * @param callable(mixed, mixed): mixed $callback Callback invoked with (value, key); truthy results keep the original value.
     * @return array<mixed> Filtered array preserving keys.
     */
    public static function map(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $isValid = call_user_func($callback, $value, $key);

            if ($isValid) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Is multidimensional array
     *
     * @param array<mixed> $array Source array.
     * @return bool True when the first element is an array.
     */
    public static function isMultidimensional(array $array): bool
    {
        return is_array(self::first($array));
    }

    /**
     * Reset array keys
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Array with sequential keys.
     */
    public static function resetKeys(array $array): array
    {
        if (self::isMultidimensional($array)) {
            return array_map("array_values", $array);
        }

        return array_values($array);
    }

    /**
     * Order
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Sorted array.
     */
    public static function order(array $array): array
    {
        sort($array);

        return $array;
    }

    /**
     * Order by
     *
     * @param array<mixed> $array Source array of rows.
     * @param string|int $by Key to sort by.
     * @return array<mixed> Sorted array.
     */
    public static function orderBy(array $array, string|int $by): array
    {
        usort($array, fn($a, $b) => $a[$by] <=> $b[$by]);

        return $array;
    }

    /**
     * Order by string
     *
     * @param array<mixed> $array Source array of rows.
     * @param string|int $by Key to sort by.
     * @return array<mixed> Sorted array.
     */
    public static function orderByString(array $array, string|int $by): array
    {
        usort($array, fn($a, $b) => strcasecmp($a[$by], $b[$by]));

        return $array;
    }

    /**
     * Random
     *
     * @param array<mixed> $array Source array.
     * @return array<mixed> Shuffled array.
     */
    public static function random(array $array): array
    {
        if (self::isMultidimensional($array)) {
            $newArray = [];
            $keys = array_keys($array);

            shuffle($keys);

            foreach ($keys as $key) {
                $newArray[$key] = $array[$key];
            }

            $array = $newArray;
        } else {
            shuffle($array);
        }

        return $array;
    }

    /**
     * Add
     * add(['name' => 'rx 5600'], 'price', 100)
     *
     * @param array<mixed> $array Source array.
     * @param mixed $key Key to set, or value to append when $value is empty.
     * @param mixed $value Value to set, defaults to null.
     * @return array<mixed> Array with the value added.
     */
    public static function add(array $array, mixed $key, mixed $value = null): array
    {
        $result = $array;

        if ($value) {
            $result[$key] = $value;
        } else {
            $result[] = $key;
        }

        return $result;
    }

    /**
     * Flatted array
     * [[1, 2, 3], [4, 5, 6], [7, 8, 9]]
     *
     * @param array<mixed> $array Nested array.
     * @return array<mixed> Flattened array.
     */
    public static function flat(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flat($value));
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Dot notation
     *
     * @param array<mixed> $array Nested array.
     * @return array<string, mixed> Array flattened with dot-notation keys.
     */
    public static function dot(array $array): array
    {
        $recursiveIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
        $result = [];

        foreach ($recursiveIterator as $leafValue) {
            $keys = [];

            foreach (range(0, $recursiveIterator->getDepth()) as $depth) {
                $keys[] = $recursiveIterator->getSubIterator($depth)->key();
            }

            $result[join(".", $keys)] = $leafValue;
        }

        return $result;
    }

    /**
     * Exists
     *
     * @param array<mixed> $array Source array.
     * @param string|int $name Key to check.
     * @return bool True when the key is set.
     */
    public static function exists(array $array, string|int $name): bool
    {
        return isset($array[$name]);
    }

    /**
     * Get array value
     *
     * @param array<mixed> $array Source array.
     * @param string $name Key in dot notation.
     * @return mixed Value at the key, or null when missing.
     */
    public static function get(array $array, string $name): mixed
    {
        $newArray = self::dot($array);

        return $newArray[$name] ?? null;
    }

    /**
     * Has an array key
     *
     * @param array<mixed> $array Source array.
     * @param string $name Key in dot notation.
     * @return bool True when the key resolves to a truthy value.
     */
    public static function has(array $array, string $name): bool
    {
        return (bool) self::get($array, $name);
    }

    /**
     * Pluck
     *
     * @param array<mixed> $array Source array of rows.
     * @param string $name Key in dot notation to extract.
     * @return array<mixed> Extracted values.
     */
    public static function pluck(array $array, string $name): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newArray = self::dot($value);

            if (isset($newArray[$name])) {
                $result[] = $newArray[$name];
            }
        }

        return $result;
    }

    /**
     * Prepend
     *
     * @param array<mixed> $array Source array.
     * @param mixed $key Value to prepend, or key when $value is given.
     * @param mixed $value Value to set for $key, defaults to null.
     * @return array<mixed> Array with the value prepended.
     */
    public static function prepend(array $array, mixed $key, mixed $value = null): array
    {
        if ($value) {
            $array = [$key => $value] + $array;
        } else {
            array_unshift($array, $key);
        }

        return $array;
    }

    /**
     * Array to query string
     *
     * @param array<mixed> $array Source array.
     * @return string Query string.
     */
    public static function query(array $array): string
    {
        $string = http_build_query($array, "", "&");
        $result = preg_replace(["/%5B/", "/%5D/"], ["[", "]"], $string);

        return is_string($result) ? $result : $string;
    }

    /**
     * Set array value
     *
     * @param array<mixed> $array Source array.
     * @param string|int $key Key to set, supports dot notation for reads.
     * @param mixed $value Value to set.
     * @return mixed Updated array, or nested value when $key uses dot notation.
     */
    public static function set(array $array, string|int $key, mixed $value): mixed
    {
        if (is_string($key) && str_contains($key, ".")) {
            $keys = explode(".", $key);
            $result = $array;

            foreach ($keys as $key) {
                $result = $result[$key];
            }

            return $result;
        }

        $array[$key] = $value;

        return $array;
    }

    /**
     * Title to slug
     *
     * @param string $title Title to slugify.
     * @return string Slug.
     */
    public static function slug(string $title): string
    {
        $slug = strtolower($title);

        $slug = preg_replace("/[^a-z0-9\s]/", "", $slug);
        $slug = is_string($slug) ? $slug : "";
        $slug = preg_replace("/\s+/", "-", $slug);

        return trim(is_string($slug) ? $slug : "", "-");
    }

    /**
     * Check if both arrays have same values
     *
     * @param array<mixed> $array1 First array.
     * @param array<mixed> $array2 Second array.
     * @return bool True when both arrays contain the same values.
     */
    public static function isSameArray(array $array1, array $array2): bool
    {
        return empty(array_diff($array1, $array2)) && empty(array_diff($array2, $array1));
    }

    /**
     * Compare arrays
     *
     * @param array<mixed> $oldArray Old values.
     * @param array<mixed> $newArray New values.
     * @return array{
     *     added: int[],
     *     removed: int[],
     *     unchanged: int[],
     *     summary: array{
     *         addedCount: int,
     *         removedCount: int,
     *         unchangedCount: int,
     *     }
     * }
     */
    public static function compareArrays(array $oldArray, array $newArray): array
    {
        return [
            "added" => array_values(array_diff($newArray, $oldArray)),
            "removed" => array_values(array_diff($oldArray, $newArray)),
            "unchanged" => array_values(array_intersect($oldArray, $newArray)),
            "summary" => [
                "addedCount" => count(array_diff($newArray, $oldArray)),
                "removedCount" => count(array_diff($oldArray, $newArray)),
                "unchangedCount" => count(array_intersect($oldArray, $newArray)),
            ],
        ];
    }

    /**
     * Convert an array to object
     *
     * @param array<mixed> $array Source array.
     * @return object Decoded object.
     */
    public static function arrayToObject(array $array): object
    {
        $json = json_encode($array);
        $decoded = json_decode(is_string($json) ? $json : "{}");

        return is_object($decoded) ? $decoded : (object) [];
    }

    /**
     * String to camel case
     *
     * @param string $text Input text.
     * @return string Camel-cased text.
     */
    public static function toCamelCase(string $text): string
    {
        $text = ucwords(str_replace(["-", "_"], " ", strtolower($text)));
        $text = str_replace(" ", "", $text);

        return lcfirst($text);
    }

    /**
     * String to titlecase
     *
     * @param string $text Input text.
     * @return string Title-cased text.
     */
    public static function toTitleCase(string $text): string
    {
        $text = str_replace(["-", "_"], " ", strtolower(trim($text)));
        $text = preg_replace("/\s+/", " ", $text);

        return ucfirst(is_string($text) ? $text : "");
    }

    /**
     * Check if it's a JSON string
     *
     * @param mixed $string Value to check.
     * @return bool True when the value is a valid JSON string.
     */
    public static function isJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        // Available from php 8.3
        if (function_exists("json_validate")) {
            return json_validate($string);
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Size of an object
     *
     * @param object $object Object to count.
     * @return int Number of properties.
     */
    public static function countObject(object $object): int
    {
        return count(get_object_vars($object));
    }

    /**
     * Pascal case to snake case
     *
     * @param string $input Pascal-cased input.
     * @return string Snake-cased output.
     */
    public static function pascalCaseToSnakeCase(string $input): string
    {
        $snake = preg_replace("/(?<!^)[A-Z]/", '_$0', $input);

        return strtolower(is_string($snake) ? $snake : $input);
    }
}
