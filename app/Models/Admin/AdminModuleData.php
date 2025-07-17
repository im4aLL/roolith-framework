<?php

namespace App\Models\Admin;

use App\Models\Model;

class AdminModuleData extends Model
{
    protected string $table = 'module_data';

    /**
     * Simplify module data by key value pair
     *
     * @param int $moduleId
     * @return array
     */
    public static function getAllByModuleId(int $moduleId): array
    {
        $result = [];

        $data = self::orm()->select([
            'condition' => "WHERE module_id = '$moduleId'"
        ])->get();

        foreach ($data as $row) {
            $result[$row->field_name] = $row->field_data;
        }

        return $result;
    }
}
