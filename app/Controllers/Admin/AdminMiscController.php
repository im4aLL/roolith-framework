<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Utils\FileManager;

class AdminMiscController extends Controller
{
    public function fileManager()
    {
        // Initialize file manager
        $fm = new FileManager();
        $currentPath = $fm->getCurrentPath();
        $message = '';

        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'create_folder':
                        if (!empty($_POST['folder_name'])) {
                            if ($fm->createDirectory($currentPath, $_POST['folder_name'])) {
                                $message = 'Folder created successfully';
                            } else {
                                $message = 'Failed to create folder';
                            }
                        }
                        break;

                    case 'upload_file':
                        if (isset($_FILES['file'])) {
                            $result = $fm->uploadFile($currentPath, $_FILES['file']);
                            $message = $result['message'];
                        }
                        break;

                    case 'delete':
                        if (!empty($_POST['item_path'])) {
                            if ($fm->deleteItem($_POST['item_path'])) {
                                $message = 'Item deleted successfully';
                            } else {
                                $message = 'Failed to delete item';
                            }
                        }
                        break;
                }
            }
        }

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
}
