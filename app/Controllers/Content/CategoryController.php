<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Category;

class CategoryController extends Controller
{
    public function index(): array
    {
        return Category::all();
    }

    /**
     * Show a category by its slug.
     *
     * @param string $slug The slug of the category to show.
     * @return object|bool|string The category object or a boolean indicating failure or a string indicating an error.
     */
    public function show($slug): object|bool|string
    {
        $category = Category::getBySlug($slug);

        if (!$category) {
            return $this->view("404", ["message" => "Page not found"]);
        }

        return $category;
    }
}
