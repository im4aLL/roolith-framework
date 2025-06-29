<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\LazyLoad;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;

class AdminPageController extends Controller
{
    public function index(): mixed
    {
        $total = AdminPage::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminPage::orm()->query('SELECT * FROM '.AdminPage::tableName())->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.pages.index')
        ]);
        $paginationData = $pagination->getDetails();

        $lazyLoad = new LazyLoad($paginationData->data);
        $lazyLoad->with(AdminCategory::class, 'category_id')->with(AdminUser::class, 'user_id')->get();

        $data = [
            'title' => 'Pages',
            'pages' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total,
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
