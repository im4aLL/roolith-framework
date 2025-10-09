<?php
namespace App\Models\Content;

use App\Models\Model;
use App\Utils\Collection;

class Category extends Model
{
    protected string $table = 'categories';

    /**
     * Get by slug
     *
     * @param $slug
     * @return bool|object
     */
    public static function getBySlug($slug): bool|object
    {
        $category = self::orm()->where('slug', $slug)->first();

        if (!$category) {
            return false;
        }

        $categoryPages = PageCategory::orm()->where("category_id", $category->id)->get();
        $categoryPageIds = Collection::make($categoryPages)->pluck("page_id")->toArray();

        if (count($categoryPageIds) === 0) {
            return $category;
        }

        $pages = Page::raw()->query("SELECT * FROM ".Page::tableName()." WHERE id IN (".implode(',', $categoryPageIds).") AND status = 'published'")->get();
        $category->pages = $pages;

        return $category;
    }
}
