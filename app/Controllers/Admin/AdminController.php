<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Admin\AdminPage;
use App\Models\Admin\AdminUser;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $currentUser = AdminUser::current();

        $pageQueryString = "SELECT
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count
            FROM ".AdminPage::tableName();
        $pageCount = AdminPage::raw()->query($pageQueryString)->first();

        $data = [
            'content' => 'Welcome to Roolith admin!',
            'title' => 'Roolith Admin',
            'lastLoggedIn' => Carbon::parse($currentUser->last_logged_in)->toDayDateTimeString(),
            'pageCount' => $pageCount,
        ];

        return $this->view('admin.admin-dashboard', $data);
    }

    public function create()
    {
    }

    public function store()
    {
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
