<?php
namespace App\Controllers;

class AdminController extends Controller
{
    public function index()
    {
        $data = [
            'content' => 'Welcome to Roolith admin!',
            'title' => 'Roolith Admin',
        ];

        return $this->view('admin.dashboard', $data);
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
