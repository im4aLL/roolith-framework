<?php
namespace App\Models\Content;

use App\Models\Model;
use App\Models\User;
use App\Utils\Collection;

class Page extends Model
{
    protected string $table = "pages";

    /**
     * Get last 10 blog items
     *
     * @return array
     */
    public static function getLatestBlogItems(): array
    {
        $queryString = "SELECT a.*,
            b.name as user_name,
            GROUP_CONCAT(CONCAT(c.name, ':', c.slug) ORDER BY c.name SEPARATOR ',') AS category_names
        FROM ".self::tableName()." as a
        LEFT JOIN ".User::tableName()." as b on b.id = a.user_id
        LEFT JOIN ".PageCategory::tableName()." AS pc ON pc.page_id = a.id
        LEFT JOIN ".Category::tableName()." AS c ON c.id = pc.category_id
            WHERE a.status = 'published' AND a.type = 'blog'
            GROUP BY a.id
            ORDER BY a.id DESC
            LIMIT 0, 10";

        return Page::raw()->query($queryString)->get();
    }

    /**
     * Get blog item by slug
     *
     * @param $slug
     * @return object|bool
     */
    public static function getBlogBySlug($slug): object|bool
    {
        $blog = self::orm()->where("slug", $slug)->where("status", "published")->where("type", "blog")->first();

        if (!$blog) {
            return false;
        }

        $blog->modules = self::_getPageModules($blog->id);
        $blog->user_name = self::_getUserNameById($blog->user_id);
        $blog->categories = self::_getCategories($blog->id);

        return $blog;
    }

    /**
     * Get page data by slug
     *
     * @param $slug
     * @return object|false
     */
    public static function getBySlug($slug): object|false
    {
        $page = self::orm()->where("slug", $slug)->where("status", "published")->where("type", "page")->first();

        if (!$page) {
            return false;
        }

        $page->modules = self::_getPageModules($page->id);

        return $page;
    }

    /**
     * Get a list of page modules with module data
     *
     * @param int $pageId
     * @return array
     */
    private static function _getPageModules(int $pageId): array
    {
        $pageModules = PageModule::orm()->select(["orderBy" => "position"])->where("page_id", $pageId)->get();

        if (count($pageModules) === 0) {
            return [];
        }

        $pageModuleIds = Collection::make($pageModules)->pluck("module_id")->toArray();
        $modules = Module::raw()->query("SELECT * FROM ".Module::tableName()." WHERE id IN (".implode(',', $pageModuleIds).") AND status = 'published'")->get();

        return Module::attachModuleData($modules);
    }

    /**
     * Get username by user id
     *
     * @param int $userId
     * @return string
     */
    private static function _getUserNameById(int $userId): string
    {
        $user = User::orm()->find($userId);

        if (!$user) {
            return '';
        }

        return $user->name;
    }

    /**
     * Get categories by page or blog id
     *
     * @param int $blogId
     * @return array
     */
    private static function _getCategories(int $blogId): array
    {
        $pageCategories = PageCategory::orm()->where("page_id", $blogId)->get();

        if (count($pageCategories) === 0) {
            return [];
        }

        $pageCategoryIds = Collection::make($pageCategories)->pluck("category_id")->toArray();

        return Category::raw()->query("SELECT * FROM ".Category::tableName()." WHERE id IN (".implode(',', $pageCategoryIds).")")->get();
    }
}
