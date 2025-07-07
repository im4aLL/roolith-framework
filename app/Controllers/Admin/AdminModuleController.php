<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminModule;

class AdminModuleController extends Controller
{
    /**
     * Show a list of modules with pagination
     *
     * @return string|bool
     */
    public function index(): string|bool
    {
        $total = AdminModule::orm()->select([
            'field' => ['id']
        ])->count();

        $pagination = AdminModule::orm()->query('SELECT * FROM ' . AdminModule::tableName() . ' ORDER by id DESC')->paginate([
            'perPage' => APP_ADMIN_PAGINATION_PER_PAGE,
            'total' => $total,
            'pageUrl' => route('admin.modules.index')
        ]);
        $paginationData = $pagination->getDetails();

        $data = [
            'title' => 'Modules',
            'modules' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        $data['isShowPagination'] = $paginationData->total > $paginationData->perPage;

        return $this->view('admin/module/admin-module', $data);
    }

    /**
     * Show add a new module form
     *
     * @return bool|string
     */
    public function create(): bool|string
    {
        $pages = AdminPage::orm()->select([
            'field' => ['id', 'title'],
            'condition' => "WHERE type='page'",
            'orderBy' => 'title',
        ])->get();

        $data = [
            'title' => 'Create Module',
            'loadEditor' => true,
            'pages' => $pages,
        ];

        return $this->view('admin/module/admin-module-create', $data);
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
