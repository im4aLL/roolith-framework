<?php
namespace App\Models\Admin;

use App\Models\Model;

class AdminModuleSetting extends Model
{
    protected string $table = "module_settings";

    /**
     * Get data with a proper settings object
     *
     * @param int $id
     * @return object|bool
     */
    public static function getById(int $id): object|bool
    {
        $data = self::orm()->find($id);
        $data->settings = json_decode($data->settings);

        return $data;
    }

    /**
     * Search for module setting by name.
     *
     * @param string $name The name to search for.
     * @return array An array of module settings that match the name.
     */
    public static function searchByName(string $name): array
    {
        return self::orm()
            ->select([
                "field" => ["id", "name"],
                "condition" => "WHERE name LIKE '$name%'",
                "limit" => "5",
                "orderBy" => "name",
            ])
            ->get();
    }
}
