<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\LazyLoad;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;

class AdminPageController extends Controller
{
    public function index(): string|bool
    {
        $pageData = AdminPage::all();
        $lazyLoad = new LazyLoad($pageData);
        $pages = $lazyLoad->with(AdminCategory::class, 'category_id')->with(AdminUser::class, 'user_id')->get();

        $data = [
            'title' => 'Pages',
            'pages' => $pages,
        ];

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
