<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Page;

class PageController extends Controller
{
    /**
     * Show a page by its slug.
     *
     * @param string $slug The slug of the page to show.
     * @return object|bool|string The page object or a boolean indicating failure or a string indicating an error.
     */
    public function show($slug): object|bool|string
    {
        $page = Page::getBySlug($slug);

        if (!$page) {
            return $this->view("404", ["message" => "Page not found"]);
        }

        return $page;
    }
}
