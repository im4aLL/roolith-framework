<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\LazyLoad;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminPageCategory;
use App\Models\Admin\AdminUser;

class AdminModuleSettingController extends Controller
{
    /**
     * Show a list of pages with pagination
     *
     * @return string|bool
     */
    public function index(): string|bool
    {
        $total = AdminModuleSetting::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminModuleSetting::orm()->query('SELECT * FROM ' . AdminModuleSetting::tableName() . ' ORDER by id DESC')->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.pages.index')
        ]);
        $paginationData = $pagination->getDetails();

        $data = [
            'title' => 'Modules',
            'moduleSettings' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        $data['isShowPagination'] = $paginationData->total > $paginationData->perPage;

        return $this->view('admin/module-setting/admin-module-setting', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Create Module Setting',
        ];

        return $this->view('admin/module-setting/admin-module-setting-create', $data);
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
