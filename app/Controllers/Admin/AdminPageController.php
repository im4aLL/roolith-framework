<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Admin\AdminPage;

class AdminPageController extends Controller
{
    public function index(): mixed
    {
        $data = [
            'title' => 'Pages'
        ];

        $pages = AdminPage::all();
        return $pages;

       return $this->view('admin/page/admin-page', $data);
    }

    public function create()
    {
    }

    public function store()
    {
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function update($id)
    {
    }

    public function destroy($id)
    {
    }
}
