<?php

namespace App\Controllers\Admin;

use App\Core\ApiResponseTransformer;
use App\Core\Request;
use App\Core\Storage;
use App\Utils\Admin\FileManager;
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
        $message = $this->_handleFileManagerFormSubmission($fm);

        // Get directory contents
        $contents = $fm->scanDirectory($currentPath);

        $data = [
            "contents" => $contents,
            "message" => $message,
            "currentPath" => $currentPath,
            "fm" => $fm,
            "title" => "File Manager",
        ];

        return $this->view("admin/misc/admin-file-manager", $data);
    }

    /**
     * Handle form submission from file manager and return message
     *
     * @param FileManager $fileManager
     * @return string
     */
    private function _handleFileManagerFormSubmission(FileManager $fileManager): string
    {
        $currentPath = $fileManager->getCurrentPath();
        $message = "";

        if (Request::method() !== "POST") {
            return $message;
        }

        if (!Request::input("action")) {
            return $message;
        }

        switch (Request::input("action")) {
            case "create_folder":
                if (!empty(Request::input("folder_name"))) {
                    $isCreated = $fileManager->createDirectory($currentPath, Request::input("folder_name"));
                    $message = $isCreated ? "Folder created successfully" : "Failed to create folder";
                }
                break;

            case "upload_file":
                if (isset($_FILES["file"])) {
                    $result = $fileManager->uploadFile($currentPath, $_FILES["file"]);
                    $message = $result["message"];
                }
                break;

            case "delete":
                if (!empty(Request::input("item_path"))) {
                    $isDeleted = $fileManager->deleteItem(Request::input("item_path"));
                    $message = $isDeleted ? "Item deleted successfully" : "Failed to delete item";
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

    /**
     * Global search
     *
     * @return array
     */
    public function globalSearch(): array
    {
        $minCharacterLength = 3;
        $queryString = Request::input("q");

        if (!$queryString) {
            return ApiResponseTransformer::error(null, "No query string");
        }

        if (strlen($queryString) < $minCharacterLength) {
            return ApiResponseTransformer::error(
                null,
                "Query string must be at least {$minCharacterLength} characters long",
            );
        }

        $results = [];
        $results[] = ["label" => "Label 1", "link" => "/label1", "type" => "page"];
        $results[] = ["label" => "Label 2", "link" => "/label2", "type" => "category"];
        $results[] = ["label" => "Label 3", "link" => "/label3", "type" => "module"];
        $results[] = ["label" => "Label 4", "link" => "/label4", "type" => "page"];

        // Perform global search logic here

        return ApiResponseTransformer::success($results);
    }
}
