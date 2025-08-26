<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Storage;
use App\Utils\FileManager;
use Carbon\Carbon;

class AdminMiscController extends AdminBaseController
{
    /**
     * Basic file manager page
     *
     * @return bool|string
     */
    public function fileManager(): bool|string
    {
        // Initialize file manager
        $fm = new FileManager();
        $fm->setBaseUrl(url(APP_ADMIN_FILE_MANAGER_DIR));
        $currentPath = $fm->getCurrentPath();

        // Handle form submissions
        $message = $this->handleFileManagerFormSubmission($fm);

        // Get directory contents
        $contents = $fm->scanDirectory($currentPath);

        $data = [
            'contents' => $contents,
            'message' => $message,
            'currentPath' => $currentPath,
            'fm' => $fm,
            'title' => 'File Manager',
        ];

        return $this->view('admin/misc/admin-file-manager', $data);
    }

    /**
     * Handle form submission from file manager and return message
     *
     * @param FileManager $fileManager
     * @return string
     */
    private function handleFileManagerFormSubmission(FileManager $fileManager): string
    {
        $currentPath = $fileManager->getCurrentPath();
        $message = '';

        if (Request::method() !== 'POST') {
            return $message;
        }

        if (!Request::input('action')) {
            return $message;
        }

        switch (Request::input('action')) {
            case 'create_folder':
                if (!empty(Request::input('folder_name'))) {
                    if ($fileManager->createDirectory($currentPath, Request::input('folder_name'))) {
                        $message = 'Folder created successfully';
                    } else {
                        $message = 'Failed to create folder';
                    }
                }
                break;

            case 'upload_file':
                if (isset($_FILES['file'])) {
                    $result = $fileManager->uploadFile($currentPath, $_FILES['file']);
                    $message = $result['message'];
                }
                break;

            case 'delete':
                if (!empty(Request::input('item_path'))) {
                    if ($fileManager->deleteItem(Request::input('item_path'))) {
                        $message = 'Item deleted successfully';
                    } else {
                        $message = 'Failed to delete item';
                    }
                }
                break;
        }

        return $message;
    }

    /**
     * Store UI states
     *
     * @return void
     */
    public function storeUiStates(): void
    {
        $states = Request::all();

        foreach ($states as $stateKey => $stateValue) {
            Storage::setCookie($stateKey, $stateValue, Carbon::now()->addYear());
        }
    }
}
