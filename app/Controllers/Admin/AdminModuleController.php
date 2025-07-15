<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Interfaces\FileInterface;
use App\Core\Request;
use App\Core\Storage;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminModule;

class AdminModuleController extends Controller
{
    private string $formErrorKey = 'module_error';

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

            if  (!$moduleSetting) {
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

        $uploaded = [];
        if (isset($formData['_files'])) {
            foreach ($formData['_files'] as $key => $file) {
                if (is_array($file)) {
                    foreach ($file as $fileData) {
                        $uploaded[$key][] = $fileData->upload(APP_ADMIN_FILE_MANAGER_DIR . 'uploads/');
                    }
                } else {
                    $uploaded[$key] = $file->upload(APP_ADMIN_FILE_MANAGER_DIR. 'uploads/');
                }
            }
        }

        p($uploaded);

        return $formData;
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
