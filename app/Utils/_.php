<?php
namespace App\Core\Utils;


class _
{
    /**
     * Get only selected array
     *
     * @param $items
     * @param $only
     * @return array
     */
    public static function only($items, $only)
    {
        $resultArray = [];

        if (is_array($only)) {
            foreach ($items as $item) {
                if (in_array($item, $only)) {
                    $resultArray[] = $item;
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
     * Exclude items from array
     *
     * @param $items
     * @param $except
     * @return array
     */
    public static function except($items, $except)
    {
        $resultArray = [];

        if (is_array($except)) {
            foreach ($items as $item) {
                if (!in_array($item, $except)) {
                    $resultArray[] = $item;
                }
            }
        } else {
            foreach ($items as $item) {
                if ($item !== $except) {
                    $resultArray[] = $item;
                }
            }
        }

        return $resultArray;
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
    public static function chunk($array, $amount, $preserveKey = false)
    {
        return array_chunk($array, $amount, $preserveKey);
    }

    /**
     * Compact
     * Creates an array with all falsey values removed. The values false, null, 0, "", undefined, and NaN are falsey.
     *
     * @param $array
     * @return array
     */
    public static function compact($array)
    {
        $resultArray = [];

        foreach ($array as $item) {
            if ($item !== null || !$item || empty($item) || ($item && strlen($item) === 0)) {
                $resultArray[] = $item;
            }
        }

        return $resultArray;
    }

    /**
     * Concat
     * Creates a new array concatenating array with any additional arrays and/or values.
     *
     * @return array
     */
    public static function concat()
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
    public static function difference()
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
    public static function drop($array, $n = 1)
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
    public static function dropRight($array, $n = 1)
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
    public static function dropWhile($array, $callback)
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
    public static function filter($array, $callback)
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
    public static function remove($array, $callback)
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
    public static function findIndex($array, $callback)
    {
        if (is_callable($callback)) {
            foreach ($array as $n) {
                $isValid = call_user_func($callback, $n);

                if ($isValid) {
                    return self::indexOf($array, $n);
                    break;
                }
            }
        } else {
            return self::indexOf($array, $callback);
        }
    }

    /**
     * Index of
     * Gets the index at which the first occurrence of value is found in array
     *
     * @param $array
     * @param $n
     * @return int
     */
    public static function indexOf($array, $n)
    {
        return array_search($n, $array);
    }

    /**
     * Join
     * Converts all elements in array into a string separated by separator.
     *
     * @param $array
     * @param string $separator
     * @return string
     */
    public static function join($array, $separator = ',')
    {
        return implode($array, $separator);
    }

    /**
     * last
     * Gets the last element of array.
     *
     * @param $array
     * @return int
     */
    public static function last($array)
    {
        return $array[count($array) - 1];
    }

    /**
     * First
     *
     * @param $array
     * @return mixed
     */
    public static function first($array)
    {
        return array_values($array)[0];
    }

    /**
     * Array reverse
     *
     * @param $array
     * @return array
     */
    public static function reverse($array)
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
    public static function take($array, $n)
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
    public static function takeRight($array, $n)
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
    public static function uniq($array)
    {
        return array_unique($array);
    }

    /**
     * Find
     * Iterates over elements of collection, returning the first element predicate returns truthy for
     *
     * @param $array
     * @param $callback
     * @param bool $withKey
     * @return array|bool|mixed
     */
    public static function find($array, $callback, $withKey = false)
    {
        foreach ($array as $k => $n) {
            $isValid = call_user_func($callback, $n);

            if ($isValid) {
                if ($withKey) {
                    return [$k => $n];
                }

                return $n;
                break;
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
    public static function each($array, $callback)
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
    public static function contains($array, $n)
    {
        foreach ($array as $key => $value) {
            if ($value === $n) {
                return true;
                break;
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
    public static function map($array, $callback)
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
     * Is multidimentional array
     *
     * @param $array
     * @return bool
     */
    public static function isMultidimensional($array)
    {
        return is_array(self::first($array));
    }

    /**
     * Reset array keys
     *
     * @param $array
     * @return array
     */
    public static function resetKeys($array)
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
    public static function order($array)
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
    public static function orderBy($array, $by)
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
    public static function orderByString($array, $by)
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
    public static function random($array)
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
}
