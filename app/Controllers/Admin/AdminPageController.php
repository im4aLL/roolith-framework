<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;

class AdminPageController extends Controller
{
    public function index(): string|bool
    {
        $total = AdminPage::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminPage::orm()->query('SELECT * FROM ' . AdminPage::tableName())->paginate([
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

        $data['isShowPagination'] = $paginationData->total > $paginationData->perPage;

        return $this->view('admin/page/admin-page', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Create Page',
            'loadEditor' => true,
        ];

        return $this->view('admin/page/admin-page-create', $data);
    }

    public function store()
    {
        $values = Request::only(['title', 'type', 'status', 'body']);

        $validator = new Validator();
        $validator->check($values, [
            'title' => Rules::set()->isRequired(),
            'type' => Rules::set()->isRequired(),
            'status' => Rules::set()->isRequired(),
            'body' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return $values;
    }

    public function show($id)
    {
        $page = AdminPage::orm()->find($id);

        $data = [
            'title' => 'Edit Page',
            'page' => $page,
            'loadEditor' => true,
        ];

        return $this->view('admin/page/admin-page-show', $data);
    }

    public function edit($id)
    {
        return $id;
    }

    public function update($id)
    {
    }

    public function destroy($id)
    {
    }
}
