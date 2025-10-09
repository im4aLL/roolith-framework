<?php
namespace App\Models\Content;

use App\Models\Model;
use App\Utils\_;
use App\Utils\Collection;

class Module extends Model
{
    protected string $table = 'modules';

    /**
     * Get by hook name
     *
     * @param $hookName string
     * @return object|false
     */
    public static function getByHook(string $hookName): object|false
    {
        $module = self::orm()->where("hook", $hookName)->where("status", "published")->first();

        if (!$module) {
            return false;
        }

        $moduleData = ModuleData::orm()->where("module_id", $module->id)->get();

        foreach ($moduleData as $data) {
            $module->{$data->field_name} = $data->field_data;
        }

        $module->theme_template = $module->hook . '-' . _::slug($module->title);

        return $module;
    }

    /**
     * Get modules by group name
     *
     * @param string $groupName
     * @return array
     */
    public static function getAllByGroup(string $groupName): array
    {
        $modules = self::orm()->where("group_name", $groupName)->where("status", "published")->get();

        if (count($modules) === 0) {
            return [];
        }

        return self::attachModuleData($modules);
    }

    /**
     * Attach module data to modules
     *
     * @param array $modules
     * @return array
     */
    public static function attachModuleData(array $modules): array
    {
        $moduleIds = Collection::make($modules)->pluck("id")->toArray();
        $moduleData = ModuleData::raw()->query("SELECT * FROM ".ModuleData::tableName()." WHERE module_id IN (".implode(',', $moduleIds).")")->get();

        foreach ($modules as $module) {
            $mData = self::_getModuleDataByModuleId($module->id, $moduleData);

            foreach ($mData as $data) {
                $module->{$data->field_name} = $data->field_data;
            }

            $module->theme_template = $module->hook . '-' . _::slug($module->title);
        }

        return $modules;
    }

    /**
     * Get module data by module id
     *
     * @param int $moduleId
     * @param iterable $moduleData
     * @return array
     */
    private static function _getModuleDataByModuleId(int $moduleId, iterable $moduleData): array
    {
        $result = [];

        foreach ($moduleData as $data) {
            if ($data->module_id === $moduleId) {
                $result[] = $data;
            }
        }

        return $result;
    }
}
