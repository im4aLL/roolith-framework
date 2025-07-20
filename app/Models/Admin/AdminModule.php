<?php
namespace App\Models\Admin;

use App\Models\Model;
use App\Utils\_;

class AdminModule extends Model
{
    protected string $table = 'modules';

    /**
     * Get all unique groups
     *
     * @return array
     */
    public static function getAllGroups(): array
    {
        $groups = [];
        $data = self::raw()->query("SELECT DISTINCT(`group_name`) FROM `".self::tableName()."`")->get();

        foreach ($data as $row) {
            $groups[] = $row->group_name;
        }

        return _::compact($groups);
    }
}
