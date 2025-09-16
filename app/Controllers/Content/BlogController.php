<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Page;

class BlogController extends Controller
{
    public function index(): object
    {
        return Page::getLatestBlogItems();
    }

    /**
     * Show a blog post by its slug.
     *
     * @param string $slug The slug of the blog post to show.
     * @return object|bool|string The blog post object or a boolean indicating failure or a string indicating an error.
     */
    public function show($slug): object|bool|string
    {
        $blog = Page::getBlogBySlug($slug);

        if (!$blog) {
            return $this->view("404", ["message" => "Page not found"]);
        }

        return $blog;
    }
}
