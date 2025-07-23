<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Admin\AdminCategory;
use App\Models\Admin\AdminMessage;
use App\Models\Admin\AdminModule;
use App\Models\Admin\AdminModuleSetting;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index(): bool|string
    {
        $currentUser = AdminUser::current();

        $pageQueryString = "SELECT
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count
            FROM ".AdminPage::tableName();
        $pageCount = AdminPage::raw()->query($pageQueryString)->first();

        $moduleQueryString = "SELECT
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count
            FROM ".AdminModule::tableName();
        $moduleCount = AdminModule::raw()->query($moduleQueryString)->first();

        $moduleSettingsCount = AdminModuleSetting::raw()->query("SELECT COUNT(id) as total from ".AdminModuleSetting::tableName())->first();
        $categoryCount = AdminCategory::raw()->query("SELECT COUNT(id) as total from ".AdminCategory::tableName())->first();
        $unreadMessageCount = AdminMessage::raw()->query("SELECT COUNT(id) as total from ".AdminMessage::tableName()." WHERE is_seen = '0'")->first();

        $data = [
            'content' => 'Welcome to Roolith admin!',
            'title' => 'Roolith Admin',
            'lastLoggedIn' => Carbon::parse($currentUser->last_logged_in)->toDayDateTimeString(),
            'pageCount' => $pageCount,
            'moduleCount' => $moduleCount,
            'moduleSettingsCount' => $moduleSettingsCount,
            'categoryCount' => $categoryCount,
            'unreadMessageCount' => $unreadMessageCount,
        ];

        return $this->view('admin.admin-dashboard', $data);
    }
}
