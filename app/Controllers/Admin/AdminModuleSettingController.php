<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminPageCategory;
use App\Models\Admin\AdminUser;
use App\Utils\_;

class AdminModuleSettingController extends Controller
{
    private string $formErrorKey = 'module_settings_error';

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
        $errors = Storage::getTemp($this->formErrorKey);

        $data = [
            'title' => 'Create Module Setting',
        ];

        if ($errors) {
            $data['error_message'] = "This isn't a buffet — you don’t get to skip the fields you don't like.";
        }

        return $this->view('admin/module-setting/admin-module-setting-create', $data);
    }

    public function store()
    {
        $data = Request::all();

        $validator = new Validator();
        $validator->check($data, [
            'name' => Rules::set()->isRequired(),
            'settings' => Rules::set()->isRequiredArray(['name', 'type']),
        ]);

        if ($validator->fails()) {
            Storage::temp($this->formErrorKey, $validator->errors());
            redirectToRoute('admin.module-settings.create');
        }

        $data['settings'] = json_encode($data['settings']);

        $insert = AdminModuleSetting::orm()->insert($data);

        if ($insert->success()) {
            redirectToRoute('admin.module-settings.index');
        }

        return 'We tried really hard... and still failed. Classic us!';
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
        $moduleSetting = AdminModuleSetting::orm()->find($id);
        $moduleSetting->settings = json_decode($moduleSetting->settings);
        $errors = Storage::getTemp($this->formErrorKey);

        $data = [
            'title' => 'Edit Module Setting - '.$moduleSetting->name,
            'moduleSetting' => $moduleSetting,
        ];

        if ($errors) {
            $data['error_message'] = "This isn't a buffet — you don’t get to skip the fields you don't like.";
        }

        return $this->view('admin/module-setting/admin-module-setting-edit', $data);
    }

    public function update($id)
    {
        $moduleSetting = AdminModuleSetting::orm()->find($id);

        $data = Request::all();

        $validator = new Validator();
        $validator->check($data, [
            'name' => Rules::set()->isRequired(),
            'settings' => Rules::set()->isRequiredArray(['name', 'type']),
        ]);

        if ($validator->fails()) {
            Storage::temp($this->formErrorKey, $validator->errors());
            redirectToRoute('admin.module-settings.edit', ['param' => $moduleSetting->id]);
        }

        $data['settings'] = json_encode($data['settings']);

        $insert = AdminModuleSetting::orm()->update($data, ['id' => $moduleSetting->id]);

        if ($insert->success()) {
            redirectToRoute('admin.module-settings.index');
        }

        return 'We tried really hard... and still failed. Classic us!';
    }

    public function destroy($id)
    {
    }
}
