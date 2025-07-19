<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ApiResponseTransformer;
use App\Core\Interfaces\FileInterface;
use App\Core\Interfaces\ValidatorInterface;
use App\Core\LazyLoad;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Storage;
use App\Core\Validator;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminModule;
use App\Models\Admin\AdminModuleData;
use App\Models\Admin\AdminPageCategory;
use App\Models\Admin\AdminUser;
use App\Utils\_;

class AdminModuleController extends Controller
{
    private string $_formErrorKey = 'module_error';
    private string $_uploadDir = APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR;
    private array $_acceptedImages = ['jpg', 'jpeg', 'png'];
    private array $_acceptedFiles = ['pdf', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];

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

        $lazyLoad = new LazyLoad($paginationData->data);
        $lazyLoad->with(AdminModuleSetting::class, 'module_setting_id')->get();

        foreach ($paginationData->data as $row) {
            $jsonString = $row->module_setting_id_data->settings;

            if (_::isJson($jsonString)) {
                $row->module_setting_id_data->settings = json_decode($jsonString);
                $row->module_setting_id_data->settings_count = count($row->module_setting_id_data->settings->name);
            }
        }

        $data = [
            'title' => 'Modules',
            'modules' => $paginationData,
            'pageNumbers' => $pagination->pageNumbers(),
            'total' => $total
        ];

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
            'acceptedImages' => $this->_getExtensionString($this->_acceptedImages),
            'acceptedFiles' => $this->_getExtensionString($this->_acceptedFiles),
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

    public function store(): array
    {
        $formData = Request::all(['skipSanitization' => true]);

        // Validation
        $validator = $this->_validateStore($formData);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors(), 'Some fields are invalid');
        }

        // Upload files if there is any
        $uploadedFiles = $this->_uploadFormDataFiles($formData);

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

        // insert module data
        $module = AdminModule::orm()->insert($moduleData, ['hook']);

        if (!$module->success()) {
            return ApiResponseTransformer::error(null, 'Error while creating module');
        }

        if ($module->isDuplicate()) {
            return ApiResponseTransformer::error(null, 'Please use different hook name');
        }

        // insert other fields data
        foreach ($otherFields as $key => $value) {
            $moduleData = AdminModuleData::orm()->insert([
                'module_id' => $module->insertedId(),
                'field_name' => $key,
                'field_data' => is_array($value) ? json_encode($value) : $value,
            ]);

            if (!$moduleData->success()) {
                // module data will get deleted automatically because of cascade
                AdminModule::orm()->delete(['id' => $module->insertedId()]);

                return ApiResponseTransformer::error(null, 'Error while saving module data');
            }
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.modules.index')]);
    }

    /**
     * Validate storing form data
     *
     * @param array $formData
     * @return ValidatorInterface
     */
    private function _validateStore(array $formData): ValidatorInterface
    {
        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'hook' => Rules::set()->isRequired()->notExists(AdminModule::class),
            'status' => Rules::set()->isRequired(),
            'module_setting_id' => Rules::set()->isRequired()->exists(AdminModuleSetting::class),
        ]);

        return $validator;
    }

    /**
     * Upload files from form data
     *
     * @param array $formData
     * @return array
     */
    private function _uploadFormDataFiles(array $formData): array
    {
        $uploaded = [];

        if (!isset($formData['_files'])) {
            return $uploaded;
        }

        foreach ($formData['_files'] as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $fileData) {
                    $uploaded[$key][] = $fileData->upload($this->_uploadDir);
                }
            } else {
                $uploaded[$key] = $file->upload($this->_uploadDir);
            }
        }

        return $uploaded;
    }

    public function show($id) {}

    public function edit($id): bool|string
    {
        $module = AdminModule::orm()->find($id);
        $moduleSetting = AdminModuleSetting::getById($module->module_setting_id);
        $moduleData = AdminModuleData::getAllByModuleId($module->id);

        $data = [
            'module' => $module,
            'moduleSettingData' => $moduleSetting,
            'moduleData' => $moduleData,
            'title' => 'Edit module - ' . $module->title,
            'loadEditor' => true,
            'acceptedImages' => $this->_getExtensionString($this->_acceptedImages),
            'acceptedFiles' => $this->_getExtensionString($this->_acceptedFiles),
        ];

        return $this->view('admin/module/admin-module-edit', $data);
    }

    public function update($id): array
    {
        $module = AdminModule::orm()->find($id);
        $originalModuleData = AdminModuleData::getAllByModuleId($module->id);

        $formData = Request::all(['skipSanitization' => true]);

        // Validation
        $validator = $this->_validateUpdate($formData);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors(), 'Some fields are invalid');
        }

        // Upload files if there is any
        $uploadedFiles = $this->_uploadFormDataFiles($formData);

        if (count($uploadedFiles) > 0) {
            foreach ($uploadedFiles as $key => $file) {
                $originalFileData = $originalModuleData[$key] ?? null;
                $newlyUploadedFileData = $file;
                $newFileData = $file;

                if ($originalFileData && strlen($originalFileData) > 0) {
                    if (is_array($file)) {
                        // add a newly added file to existing files
                        $newFileData = array_merge((array) json_decode($originalFileData), $newlyUploadedFileData);
                    } else {
                        // delete the previous image and add a new file
                        $this->_deleteModuleDataFile($originalFileData);
                    }
                }

                $formData[$key] = is_array($newFileData) ? json_encode($newFileData) : $newFileData;
            }
        }

        if (isset($formData['_files'])) {
            unset($formData['_files']);
        }

        $moduleDataFields = ['title', 'status'];
        $moduleData = _::only($formData, $moduleDataFields);
        $otherFields = _::except($formData, $moduleDataFields);

        $isModuleDataChanged = isDataChanged($moduleData, (array) $module);

        // update module data
        if ($isModuleDataChanged) {
            $moduleUpdate = AdminModule::orm()->update($moduleData, ['id' => $module->id]);

            if (!$moduleUpdate->success()) {
                return ApiResponseTransformer::error(null, 'Error while updating module');
            }
        }

        // update other fields data
        foreach ($otherFields as $key => $value) {
            $newValue = is_array($value) ? json_encode($value) : $value;
            $oldValue = $originalModuleData[$key] ?? null;
            $isModuleDataChanged = $newValue != $oldValue;

            if (!$isModuleDataChanged) {
                continue;
            }

            if ($oldValue) {
                $moduleDataUpdate = AdminModuleData::orm()->update([
                    'field_data' => $newValue,
                ], [
                    'module_id' => $module->id,
                    'field_name' => $key,
                ]);
            } else {
                $moduleDataUpdate = AdminModuleData::orm()->insert([
                    'module_id' => $module->id,
                    'field_name' => $key,
                    'field_data' => $newValue,
                ]);
            }

            if (!$moduleDataUpdate->success()) {
                return ApiResponseTransformer::error(null, 'Error while updating module data');
            }
        }

        return ApiResponseTransformer::success(['redirect' => route('admin.modules.edit', ['param' => $module->id])]);
    }

    /**
     * Validate storing form data
     *
     * @param array $formData
     * @return ValidatorInterface
     */
    private function _validateUpdate(array $formData): ValidatorInterface
    {
        $validator = new Validator();
        $validator->check($formData, [
            'title' => Rules::set()->isRequired(),
            'status' => Rules::set()->isRequired(),
        ]);

        return $validator;
    }

    public function destroy($id) {}

    /**
     * Delete file and record
     *
     * @return array
     */
    public function deleteFile(): array
    {
        $data = Request::all(['skipSanitization' => true]);

        // validate request
        $validator = new Validator();
        $validator->check($data, [
            'moduleId' => Rules::set()->isRequired()->exists(AdminModule::class),
            'fileName' => Rules::set()->isRequired(),
            'moduleDataId' => Rules::set()->isRequired()->exists(AdminModuleData::class),
        ]);

        // if the file name is empty or false fixing those records in db
        if ($validator->fails()) {
            $errors = $validator->errors();

            if (isset($errors['fileName']) && !isset($errors['moduleId']) && !isset($errors['moduleDataId'])) {
                $this->_fixFalseFilenameInModuleData($data);
            }

            return ApiResponseTransformer::success([]);
        }

        // get module data
        $moduleData = AdminModuleData::findFile($data['moduleId'], $data['fileName']);

        if (!$moduleData) {
            return ApiResponseTransformer::error(null, 'File does not have any record');
        }

        // physically delete the file
        $deleteFile = $this->_deleteModuleDataFile($data['fileName']);

        if (!$deleteFile) {
            return ApiResponseTransformer::error(null, 'Unable to delete file');
        }

        // update db record
        $fileData = _::isJson($moduleData->field_data) ? json_decode($moduleData->field_data) : $moduleData->field_data;

        if (is_array($fileData)) {
            $updatedFileData = _::exceptByValue($fileData, [$data['fileName']]);

            if (count($updatedFileData) > 0) {
                // remove the file name and update module data record
                $field_data = json_encode(array_values($updatedFileData));
                AdminModuleData::orm()->update(['field_data' => $field_data], ['id' => $moduleData->id]);
            } else {
                // since it was only one file, delete the whole record
                AdminModuleData::orm()->delete(['id' => $moduleData->id]);
            }
        } else {
            // delete the whole record
            AdminModuleData::orm()->delete(['id' => $moduleData->id]);
        }

        return ApiResponseTransformer::success([]);
    }

    /**
     * Fix false valued filename in module data
     *
     * @param array $data
     * @return void
     */
    private function _fixFalseFilenameInModuleData(array $data): void
    {
        $moduleDataId = $data['moduleDataId'];
        $moduleData = AdminModuleData::orm()->find($moduleDataId);

        $fileData = _::isJson($moduleData->field_data) ? json_decode($moduleData->field_data) : $moduleData->field_data;

        if (is_array($fileData)) {
            // remove all falsy values
            $updatedFileData = _::compact($fileData);

            if (count($updatedFileData) > 0) {
                $field_data = json_encode(array_values($updatedFileData));
                AdminModuleData::orm()->update(['field_data' => $field_data], ['id' => $moduleData->id]);
            } else {
                AdminModuleData::orm()->delete(['id' => $moduleData->id]);
            }
        } else {
            AdminModuleData::orm()->delete(['id' => $moduleData->id]);
        }
    }

    /**
     * Delete module data file
     *
     * @param string $fileName
     * @return bool
     */
    private function _deleteModuleDataFile(string $fileName): bool
    {
        $path = APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR . $fileName;

        if (file_exists($path)) {
            unlink($path);

            return true;
        }

        return false;
    }

    /**
     * Get a string of accepted file extensions for input field
     *
     * @param array $extensions
     * @return string
     */
    private function _getExtensionString(array $extensions): string
    {
        return implode(', ', array_map(function ($ext) {
            return '.' . $ext;
        }, $extensions));
    }
}
