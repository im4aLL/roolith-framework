<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\DatabaseFactory;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminMessage;
use App\Models\Admin\AdminModule;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminSetting;
use App\Models\Admin\AdminUser;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AdminBaseController extends Controller
{
    public function view($filename, array $data = []): bool|string
    {
        $count = $this->_getAllCounts();

        $isAnalyticsEnabled = false;
        $enableAnalytics = AdminSetting::orm()->where('item', 'enable-analytics')->first();
        if ($enableAnalytics) {
            $isAnalyticsEnabled = $enableAnalytics->value == 'true' ? true : false;
        }

        $data['global'] = [
            'page_count' => $count->page_count,
            'category_count' => $count->category_count,
            'module_count' => $count->module_count,
            'unread_message_count' => $count->unread_message_count,
            'module_settings_count' => $count->module_settings_count,
            'media_size' => $this->_getMediaFolderSize(),
            'user' => AdminUser::current(),
            'isAnalyticsEnabled' => $isAnalyticsEnabled,
        ];

        return parent::view($filename, $data);
    }

    /**
     * Get all counts
     *
     * @return object{page_count:int, category_count:int, module_count: int, module_settings_count:int, unread_message_count:int}
     */
    private function _getAllCounts(): object
    {
        $db = DatabaseFactory::getInstance();
        return $db->query("SELECT
                        (SELECT COUNT(*) FROM " . AdminPage::tableName() . " WHERE status = 'published') as page_count,
                        (SELECT COUNT(*) FROM " . AdminCategory::tableName() . ") as category_count,
                        (SELECT COUNT(*) FROM " . AdminModule::tableName() . " WHERE status = 'published') as module_count,
                        (SELECT COUNT(*) FROM " . AdminModuleSetting::tableName() . ") as module_settings_count,
                        (SELECT COUNT(*) FROM " . AdminMessage::tableName() . " WHERE is_seen = 0) as unread_message_count")->first();
    }

    /**
     * Get media folder size
     *
     * @return string
     */
    private function _getMediaFolderSize(): string
    {
        $size = 0;

        if (!is_dir(APP_ADMIN_FILE_MANAGER_DIR)) {
            return '0 B';
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ADMIN_FILE_MANAGER_DIR, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $this->_formatSize($size);
    }

    /**
     * Bytes to human-readable size
     *
     * @param int $bytes
     * @return string
     */
    private function _formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes) . " " . $units[$i];
    }
}
