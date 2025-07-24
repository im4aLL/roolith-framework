<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Page;

class BlogController extends Controller
{
    public function index(): array
    {
        return Page::getLatestBlogItems();
    }

    public function show($slug): object|bool|string
    {
        $blog = Page::getBlogBySlug($slug);

        if (!$blog) {
            return $this->view('404', ["message" => "Page not found"]);
        }

        return $blog;
    }
}
