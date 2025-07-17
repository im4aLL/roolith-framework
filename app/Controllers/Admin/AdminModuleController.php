<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Interfaces\FileInterface;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminModule;
use App\Models\Admin\AdminModuleData;
use App\Utils\_;

class AdminModuleController extends Controller
{
    private string $formErrorKey = 'module_error';
    private string $uploadDir = APP_ADMIN_FILE_MANAGER_DIR . 'uploads/';

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
        // load all module settings for select input
        $moduleSettings = AdminModuleSetting::orm()->select([
            'field' => ['id', 'name'],
        ])->get();

        // generic data for template
        $data = [
            'title' => 'Create Module',
            'loadEditor' => true,
            'moduleSettings' => $moduleSettings,
        ];

        // load selected module setting
        $moduleSettingId = Request::input('module_setting_id');

        if ($moduleSettingId) {
            $moduleSetting = AdminModuleSetting::orm()->find($moduleSettingId);

            if (!$moduleSetting) {
                $data['setting_load_error_message'] = "Unable to load module setting";
            } else {
                $moduleSetting->settings = json_decode($moduleSetting->settings);
                $data['moduleSettingData'] = $moduleSetting;
            }
        }

        return $this->view('admin/module/admin-module-create', $data);
    }

    public function store()
    {
        $formData = Request::all();

        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'hook' => Rules::set()->isRequired()->notExists(AdminModule::class),
            'status' => Rules::set()->isRequired(),
            'module_setting_id' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors(), 'Some fields are invalid');
        }

        $uploadedFiles = $this->uploadFormDataFiles($formData);

        if (count($uploadedFiles) > 0) {
            foreach ($uploadedFiles as $key => $file) {
                $formData[$key] = is_array($file) ? json_encode($file) : $file;
            }
        }

        if (isset($formData['_files'])) {
            unset($formData['_files']);
        }

        $moduleDataFields = ['title', 'hook', 'status', 'module_setting_id'];

        $moduleData = _::only($formData, $moduleDataFields);
        $otherFields = _::except($formData, $moduleDataFields);

        return [
            'moduleData' => $moduleData,
            'otherFields' => $otherFields,
            'uploadedFiles' => $uploadedFiles,
        ];

        // insert module data
        $module = AdminModule::orm()->insert($moduleData, ['hook']);

        if (!$module->success()) {
            return ApiResponseTransformer::error(null, 'Error while creating module');
        }

        // insert other fields data
        foreach ($otherFields as $key => $value) {
            $moduleData = AdminModuleData::orm()->insert([
                'module_id' => $module->id,
                'field_name' => $key,
                'field_data' => is_array($value) ? json_encode($value) : $value,
            ]);

            if (!$moduleData->success()) {
                AdminModuleData::orm()->delete(['module_id' => $module->id]);

                return ApiResponseTransformer::error(null, 'Error while saving module data');
            }
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.modules.index')]);
    }

    /**
     * Upload files from form data
     *
     * @param array $formData
     * @return array
     */
    private function uploadFormDataFiles(array $formData): array
    {
        $uploaded = [];

        if (!isset($formData['_files'])) {
            return $uploaded;
        }

        foreach ($formData['_files'] as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $fileData) {
                    $uploaded[$key][] = $fileData->upload($this->uploadDir);
                }
            } else {
                $uploaded[$key] = $file->upload($this->uploadDir);
            }
        }

        return $uploaded;
    }

    public function show($id) {}

    public function edit($id) {}

    public function update($id) {}

    public function destroy($id) {}
}
