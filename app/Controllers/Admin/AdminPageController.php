<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;
use App\Utils\_;
use App\Utils\Str;

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
        $lazyLoad->with(AdminUser::class, 'user_id')->get();

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
        $categories = AdminCategory::all();

        $data = [
            'title' => 'Create Page',
            'loadEditor' => true,
            'categories' => $categories,
        ];

        return $this->view('admin/page/admin-page-create', $data);
    }

    public function store()
    {
        $formData = Request::only(['title', 'type', 'status', 'body', 'category_id']);

        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'type' => Rules::set()->isRequired(),
            'status' => Rules::set()->isRequired(),
            'body' => Rules::set()->isRequired(),
            'category_id' => Rules::set()->isArray(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors());
        }

        $formData['slug'] = _::slug($formData['title']);
        $formData['user_id'] = AdminUser::getUserId();
        $categories = $formData['category_id'];
        unset($formData['category_id']);

        return $formData;
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
