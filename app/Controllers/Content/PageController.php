<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Module;
use App\Models\Content\Page;

class PageController extends Controller
{
    public function show($slug): object|bool|string
    {
        $page = Page::getBySlug($slug);

        if (!$page) {
            return $this->view('404', ["message" => "Page not found"]);
        }

        return $page;
    }
}
