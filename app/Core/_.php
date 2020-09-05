<?php
namespace App\Core;


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
}