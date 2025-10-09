<?php

namespace App\Controllers\Admin\Traits;

use App\Core\Request;

trait AdminFilterTrait
{
    /**
     * Filter data
     *
     * @return array
     */
    public function filterData(): array
    {
        $filterInput = Request::input('filter');
        $filterUrlString = null;

        $selectArray = [
            'field' => ['id']
        ];

        if ($filterInput) {
            $whereConditions = [];

            foreach ($filterInput as $key => $value) {
                if (!$value) {
                    continue;
                }

                if ($key == 'title') {
                    $whereConditions[] = "$key LIKE '%$value%'";
                } else {
                    $whereConditions[] = "$key = '$value'";
                }
            }

            $selectArray = [
                'field' => ['id'],
                'condition' => 'WHERE ' . implode(' AND ', $whereConditions)
            ];

            $filterUrlString = generateFilterUrlString($filterInput);
        }

        return [
            'selectArray' => $selectArray,
            'filterUrlString' => $filterUrlString,
            'filterInput' => $filterInput,
            'whereConditions' => $whereConditions ?? []
        ];
    }
}
