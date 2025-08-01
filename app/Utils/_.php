<?php
namespace App\Utils;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class _
{
    /**
     * Is associative array
     *
     * @param $array
     * @return bool
     */
    public static function isAssociativeArray($array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Get only a selected array
     *
     * @param $items
     * @param $only
     * @return array
     */
    public static function only($items, $only): array
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
     * @param $items
     * @param $except
     * @return array
     */
    public static function except($items, $except): array
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
     * @param array $array
     * @param array $values
     * @return array
     */
    public static function exceptByValue(array $array, array $values): array
    {
        return array_filter($array, fn($v) => !in_array($v, $values, true));
    }

    /**
     * Array chunk
     * Creates an array of elements split into groups the length of size. If array can't be split evenly, the final chunk will be the remaining elements.
     *
     * @param $array
     * @param $amount
     * @param bool $preserveKey
     * @return array
     */
    public static function chunk($array, $amount, bool $preserveKey = false): array
    {
        return array_chunk($array, $amount, $preserveKey);
    }

    /**
     * Compact
     * Creates an array with all false values removed. The values are false, null, 0, "", undefined, and NaN are false.
     *
     * @param $array
     * @return array
     */
    public static function compact($array): array
    {
        return array_filter($array, fn($value) => (bool) $value);
    }

    /**
     * Concat
     * Creates a new array concatenating array with any additional arrays and/or values.
     *
     * @return array
     */
    public static function concat(): array
    {
        $arguments = func_get_args();
        $result = [];

        foreach ($arguments as $argument) {
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
     * @return array
     */
    public static function difference(): array
    {
        $arguments = func_get_args();

        return call_user_func_array('array_diff', $arguments);
    }

    /**
     * Drop
     * Creates a slice of array with n elements dropped from the beginning.
     *
     * @param $array
     * @param int $n
     * @return array
     */
    public static function drop($array, int $n = 1): array
    {
        return array_slice($array, $n);
    }

    /**
     * Drop right
     * Creates a slice of array with n elements dropped from the end.
     *
     * @param $array
     * @param int $n
     * @return array
     */
    public static function dropRight($array, int $n = 1): array
    {
        return array_slice($array, 0, (count($array) - $n));
    }

    /**
     * Drop while
     * Creates a slice of array excluding elements dropped from the beginning. Elements are dropped until predicate returns falsey.
     * The predicate is invoked with three arguments: (value, index, array).
     *
     * @param $array
     * @param $callback
     * @return array
     */
    public static function dropWhile($array, $callback): array
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
     * @param $array
     * @param $callback
     * @return array
     */
    public static function filter($array, $callback): array
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
     * @param $array
     * @param $callback
     * @return array
     */
    public static function remove($array, $callback): array
    {
        return self::dropWhile($array, $callback);
    }

    /**
     * Find index
     *
     * @param $array
     * @param $callback
     * @return int
     */
    public static function findIndex($array, $callback): int
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
     * @param $array
     * @param $n
     * @return int
     */
    public static function indexOf($array, $n): int
    {
        return array_search($n, $array);
    }

    /**
     * Join
     * Converts all elements in an array into a string separated by separator.
     *
     * @param $array
     * @param string $separator
     * @return string
     */
    public static function join($array, string $separator = ','): string
    {
        return implode($separator, $array);
    }

    /**
     * last
     * Gets the last element of array.
     *
     * @param $array
     * @return int
     */
    public static function last($array): int
    {
        return $array[count($array) - 1];
    }

    /**
     * First
     *
     * @param $array
     * @return mixed
     */
    public static function first($array): mixed
    {
        return array_values($array)[0];
    }

    /**
     * Array reverse
     *
     * @param $array
     * @return array
     */
    public static function reverse($array): array
    {
        return array_reverse($array);
    }

    /**
     * Take
     * Creates a slice of array with n elements taken from the beginning.
     *
     * @param $array
     * @param $n
     * @return array
     */
    public static function take($array, $n): array
    {
        return array_slice($array, 0, $n);
    }

    /**
     * Take right
     * Creates a slice of array with n elements taken from the end.
     *
     * @param $array
     * @param $n
     * @return array
     */
    public static function takeRight($array, $n): array
    {
        $length = count($array);

        return array_slice($array, ($length - $n), $length);
    }

    /**
     * Uniq
     *
     * @param $array
     * @return array
     */
    public static function uniq($array): array
    {
        return array_unique($array);
    }

    /**
     * Find
     * Iterates over elements of a collection, returning the first element predicate returns truthy for
     *
     * @param $array
     * @param $callback
     * @param bool $withKey
     * @return array|bool|mixed
     */
    public static function find($array, $callback, bool $withKey = false): mixed
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
     * @param $array
     * @param $callback
     */
    public static function each($array, $callback): void
    {
        if (is_callable($callback)) {
            foreach ($array as $itemKey => $item) {
                call_user_func($callback, $item, $itemKey);
            }
        }
    }

    /**
     * Contains
     *
     * @param $array
     * @param $n
     * @return bool
     */
    public static function contains($array, $n): bool
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
     * @param $array
     * @param $callback
     * @return array
     */
    public static function map($array, $callback): array
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
     * @param $array
     * @return bool
     */
    public static function isMultidimensional($array): bool
    {
        return is_array(self::first($array));
    }

    /**
     * Reset array keys
     *
     * @param $array
     * @return array
     */
    public static function resetKeys($array): array
    {
        if (self::isMultidimensional($array)) {
            return array_map('array_values', $array);
        }

        return array_values($array);
    }

    /**
     * Order
     *
     * @param $array
     * @return array
     */
    public static function order($array): array
    {
        sort($array);

        return $array;
    }

    /**
     * Order by
     *
     * @param $array
     * @param $by
     * @return array
     */
    public static function orderBy($array, $by): array
    {
        usort($array, function($a, $b) use ($by) {
            return $a[$by] - $b[$by];
        });

        return $array;
    }

    /**
     * Order by string
     *
     * @param $array
     * @param $by
     * @return array
     */
    public static function orderByString($array, $by): array
    {
        usort($array, function($a, $b) use ($by) {
            return strcasecmp($a[$by], $b[$by]);
        });

        return $array;
    }

    /**
     * Random
     *
     * @param $array
     * @return array
     */
    public static function random($array): array
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
     * @param $array
     * @param $key
     * @param $value null
     * @return array
     */
    public static function add($array, $key, $value = null): array
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
     * @param $array
     * @return array
     */
    public static function flat($array): array
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
     * @param $array
     * @return array
     */
    public static function dot($array): array
    {
        $recursiveIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
        $result = [];

        foreach ($recursiveIterator as $leafValue) {
            $keys = [];

            foreach (range(0, $recursiveIterator->getDepth()) as $depth) {
                $keys[] = $recursiveIterator->getSubIterator($depth)->key();
            }

            $result[join('.', $keys)] = $leafValue;
        }

        return $result;
    }

    /**
     * Exists
     *
     * @param $array
     * @param $name
     * @return bool
     */
    public static function exists($array, $name): bool
    {
        return isset($array[$name]);
    }

    /**
     * Get array value
     *
     * @param $array
     * @param $name
     * @return mixed|null
     */
    public static function get($array, $name): mixed
    {
        $newArray = self::dot($array);

        return $newArray[$name] ?? null;
    }

    /**
     * Has an array key
     *
     * @param $array
     * @param $name
     * @return bool
     */
    public static function has($array, $name): bool
    {
        return (bool) self::get($array, $name);
    }

    /**
     * Pluck
     *
     * @param $array
     * @param $name
     * @return array
     */
    public static function pluck($array, $name): array
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
     * @param $array
     * @param $key
     * @param $value null
     * @return array
     */
    public static function prepend($array, $key, $value = null): array
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
     * @param $array
     * @return string
     */
    public static function query($array): string
    {
        $string = http_build_query($array, '', '&');

        return preg_replace(['/%5B/', '/%5D/'], ['[', ']'], $string);
    }

    /**
     * Set array value
     *
     * @param $array
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function set($array, $key, $value): mixed
    {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
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
     * @param string $title
     * @return string
     */
    public static function slug(string $title): string {
        $slug = strtolower($title);

        $slug = preg_replace('/[^a-z0-9\s]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Check if both arrays have same values
     *
     * @param array $array1
     * @param array $array2
     * @return bool
     */
    public static function isSameArray(array $array1, array $array2): bool
    {
        return empty(array_diff($array1, $array2)) && empty(array_diff($array2, $array1));
    }

    /**
     * Compare arrays
     *
     * @param array $oldArray
     * @param array $newArray
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
    public static function compareArrays(array $oldArray, array $newArray): array {
        return [
            'added' => array_values(array_diff($newArray, $oldArray)),
            'removed' => array_values(array_diff($oldArray, $newArray)),
            'unchanged' => array_values(array_intersect($oldArray, $newArray)),
            'summary' => [
                'addedCount' => count(array_diff($newArray, $oldArray)),
                'removedCount' => count(array_diff($oldArray, $newArray)),
                'unchangedCount' => count(array_intersect($oldArray, $newArray))
            ]
        ];
    }

    /**
     * Convert an array to object
     *
     * @param array $array
     * @return object
     */
    public static function arrayToObject(array $array): object
    {
        return json_decode(json_encode($array));
    }

    /**
     * String to camel case
     *
     * @param string $text
     * @return string
     */
    public static function toCamelCase(string $text): string
    {
        $text = ucwords(str_replace(['-', '_'], ' ', strtolower($text)));
        $text = str_replace(' ', '', $text);

        return lcfirst($text);
    }

    /**
     * String to titlecase
     *
     * @param string $text
     * @return string
     */
    public static function toTitleCase(string $text): string
    {
        $text = str_replace(['-', '_'], ' ', strtolower(trim($text)));
        $text = preg_replace('/\s+/', ' ', $text);

        return ucfirst($text);
    }

    /**
     * Check if it's a JSON string
     *
     * @param mixed $string
     * @return bool
     */
    public static function isJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        // Available from php 8.3
        if (function_exists('json_validate')) {
            return json_validate($string);
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Size of an object
     *
     * @param object $object
     * @return int
     */
    public static function countObject(object $object): int
    {
        return count(get_object_vars($object));
    }

    /**
     * Pascal case to snake case
     *
     * @param string $input
     * @return string
     */
    public static function pascalCaseToSnakeCase(string $input): string
    {
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $input);

        return strtolower($snake);
    }
}
