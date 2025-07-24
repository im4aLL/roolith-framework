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

    public function show($slug): object|bool|string
    {
        $category = Category::getBySlug($slug);

        if (!$category) {
            return $this->view('404', ["message" => "Page not found"]);
        }

        return $category;
    }
}
