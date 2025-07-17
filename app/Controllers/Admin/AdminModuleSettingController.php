<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Exceptions\Exception;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminModuleSetting;

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
            'pageUrl' => route('admin.module-settings.index')
        ]);
        $paginationData = $pagination->getDetails();

        $data = [
            'title' => 'Modules',
            'moduleSettings' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

        return $this->view('admin/module-setting/admin-module-setting', $data);
    }

    public function create(): bool|string
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

    public function store(): string
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
        throw new Exception("Nothing here for module settings id $id");
    }

    public function edit($id): bool|string
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

    public function update($id): string
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

    public function destroy($id): array
    {
        $moduleSetting = AdminModuleSetting::orm()->find($id);

        if (!$moduleSetting) {
            return ApiResponseTransformer::error(null, 'Well, this is awkward. You\'re trying to delete a page that\'s already living its best ghost life.');
        }

        $delete = AdminModuleSetting::orm()->delete(['id' => $id]);

        if (!$delete->success()) {
            return ApiResponseTransformer::error(null, 'Error! This item seems to have developed an unhealthy attachment to us. We just can\'t seem to get rid of it!');
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.module-settings.index')]);
    }
}
