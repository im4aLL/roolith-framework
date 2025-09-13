<?php
namespace App\Models\Admin;

use App\Models\Model;

class AdminCategory extends Model
{
    protected string $table = "categories";

    /**
     * Search for category by name.
     *
     * @param string $name The name to search for.
     * @return array An array of categories that match the name.
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
