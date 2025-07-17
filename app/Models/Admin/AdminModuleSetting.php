<?php
namespace App\Models\Admin;

use App\Models\Model;

class AdminModuleSetting extends Model
{
    protected string $table = 'module_settings';

    /**
     * Get data with a proper settings object
     *
     * @param int $id
     * @return object|bool
     */
    public static function getById(int $id): object|bool
    {
        $data = self::orm()->find($id);
        $data->settings = json_decode($data->settings);

        return $data;
    }
}
