<?php

namespace App\Controllers\Admin;

use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Core\Rules;
use App\Core\Validator;
use App\Models\Admin\AdminSetting;
use App\Utils\Collection;

class AdminSettingController extends AdminBaseController
{
    public function index(): bool|string
    {
        $all = AdminSetting::all();

        $collection = Collection::make($all);
        $settingsData = $collection->where('type', '==', 'general');
        $analyticsFeature = $collection->where('item', '==', 'enable-analytics')->first();

        $data = [
            'title' => 'Site Settings',
            'settingsData' => $settingsData,
            'analyticsFeature' => $analyticsFeature,
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

    /**
     * Turn on or off a feature in site settings
     *
     * @return array
     */
    public function toggleFeature(): array
    {
        $value = Request::input('value');
        $item = Request::input('item');

        $feature = AdminSetting::orm()->where('type', 'feature')->where('item', $item)->first();

        if ($feature) {
            $update = AdminSetting::orm()->update(["value" => $value], ["item" => $item]);

            if ($update->success()) {
                return ApiResponseTransformer::success(null, "$item is set to $value");
            }
        } else {
            $insert = AdminSetting::orm()->insert(["item" => $item, "value" => $value, "type" => 'feature']);

            if ($insert->success()) {
                return ApiResponseTransformer::success(null, "$item is added to settings and set to $value");
            }
        }

        return ApiResponseTransformer::error(null, "Unable to add or update settings");
    }
}
