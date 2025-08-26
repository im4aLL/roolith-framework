<?php
namespace App\Controllers\Admin;

use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminSetting;

class AdminSettingController extends AdminBaseController
{
    public function index(): bool|string
    {
        $settingsData = AdminSetting::all();

        $data = [
            'title' => 'Site Settings',
            'settingsData' => $settingsData,
        ];

        return $this->view('admin/misc/admin-site-settings', $data);
    }

    public function store(): array
    {
        $formData = Request::only(['item', 'value']);

        $validator = new Validator();
        $validator->check($formData, [
            'item' => Rules::set()->isRequired(),
            'value' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors(), "Some fields are missing");
        }

        $insert = AdminSetting::orm()->insert($formData, ['item']);

        if ($insert->isDuplicate()) {
            return ApiResponseTransformer::error(['isDuplicate' => true], "Please use a different item.");
        }

        if (!$insert->success()) {
            return ApiResponseTransformer::error(null, "Unable to save settings.");
        }

        return ApiResponseTransformer::success(['id' => $insert->insertedId()], "Settings saved successfully.");
    }

    public function update($id): array
    {
        $data = AdminSetting::orm()->find($id);

        if (!$data) {
            return ApiResponseTransformer::error(null, "Setting not found.");
        }

        $formData = Request::only(['item', 'value']);

        $validator = new Validator();
        $validator->check($formData, [
            'item' => Rules::set()->isRequired(),
            'value' => Rules::set()->isRequired(),
        ]);

        if ($validator->fails()) {
            return ApiResponseTransformer::error($validator->errors(), "Some fields are missing");
        }

        $update = AdminSetting::orm()->update($formData, ['id' => $id], ['item']);

        if ($update->isDuplicate()) {
            return ApiResponseTransformer::error(['isDuplicate' => true], "Please use a different item.");
        }

        if (!$update->success()) {
            return ApiResponseTransformer::error(null, "Unable to save settings.");
        }

        return ApiResponseTransformer::success(null, "Settings saved successfully.");
    }

    public function destroy($id): array
    {
        $data = AdminSetting::orm()->find($id);

        if (!$data) {
            return ApiResponseTransformer::error(null, "Setting not found.");
        }

        $delete = AdminSetting::orm()->delete(['id' => $id]);

        if (!$delete->success()) {
            return ApiResponseTransformer::error(null, "Unable to delete settings.");
        }

        return ApiResponseTransformer::success(null, "Setting deleted successfully.");
    }
}
