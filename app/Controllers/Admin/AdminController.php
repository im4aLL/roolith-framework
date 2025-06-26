<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;

class AdminController extends Controller
{
    public function index()
    {
        $data = [
            'content' => 'Welcome to Roolith admin!',
            'title' => 'Roolith Admin',
        ];

        return $this->view('admin.admin-dashboard', $data);
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
