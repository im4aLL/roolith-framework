<?php
namespace App\Controllers\Content;

use App\Controllers\Controller;
use App\Models\Content\Page;

class PageController extends Controller
{
    public function index()
    {
        return 'index page of cms';
    }

    public function show($slug)
    {
        $page = Page::getBySlug($slug);

        if (!$page) {
            return $this->view('404', ["message" => "Page not found"]);
        }

        return $page;
    }
}
