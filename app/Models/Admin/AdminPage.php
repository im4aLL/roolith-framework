<?php
namespace App\Models\Admin;

use App\Models\Model;

class AdminPage extends Model
{
    protected string $table = "pages";
    public const publishedStatus = "published";
    public const draftStatus = "draft";

    /**
     * Search for pages by title.
     *
     * @param string $title The title to search for.
     * @return array An array of pages that match the title.
     */
    public static function searchByTitle(string $title): array
    {
        return self::orm()
            ->select([
                "field" => ["id", "title"],
                "condition" => "WHERE title LIKE '$title%'",
                "limit" => "5",
                "orderBy" => "title",
            ])
            ->get();
    }
}
