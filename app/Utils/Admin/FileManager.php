<?php

namespace App\Utils\Admin;

class FileManager
{
    private string $basePath;
    private array $allowedExtensions = [
        "txt",
        "pdf",
        "jpg",
        "jpeg",
        "png",
        "gif",
        "doc",
        "docx",
        "zip",
        "ppt",
        "pptx",
        "xls",
        "xlsx",
    ];
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB
    private string $baseUrl;

    public function __construct(string $basePath = "./files")
    {
        $this->basePath = rtrim($basePath, "/");

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getCurrentPath(): string
    {
        return $_GET["path"] ?? "";
    }

    public function getFullPath(string $relativePath = ""): string
    {
        $fullPath = $this->basePath . "/" . ltrim($relativePath, "/");
        return realpath($fullPath) ?: $fullPath;
    }

    public function isPathValid(string $path): bool
    {
        $realPath = realpath($path);
        $realBasePath = realpath($this->basePath);
        return $realPath && str_starts_with($realPath, $realBasePath);
    }

    /**
     * Scan a directory and return its contents.
     *
     * @param string $path The path to scan.
     * @return array An array containing directories and files.
     */
    public function scanDirectory(string $path = ""): array
    {
        $fullPath = $this->getFullPath($path);
        if (!$this->isPathValid($fullPath) || !is_dir($fullPath)) {
            return [];
        }

        $items = scandir($fullPath);
        $result = ["directories" => [], "files" => []];

        foreach ($items as $item) {
            if ($item === "." || $item === "..") {
                continue;
            }

            $itemPath = "{$fullPath}/{$item}";
            $relativePath = $path ? "{$path}/{$item}" : $item;

            if (is_dir($itemPath)) {
                $result["directories"][] = [
                    "name" => $item,
                    "path" => $relativePath,
                    "size" => $this->getDirectorySize($itemPath),
                    "modified" => filemtime($itemPath),
                ];
            } else {
                $result["files"][] = [
                    "name" => $item,
                    "path" => $relativePath,
                    "size" => filesize($itemPath),
                    "modified" => filemtime($itemPath),
                    "extension" => pathinfo($item, PATHINFO_EXTENSION),
                ];
            }
        }

        return $result;
    }

    public function createDirectory(string $path, string $name): bool
    {
        $fullPath = $this->getFullPath("{$path}/{$name}");

        if (!$this->isPathValid(dirname($fullPath))) {
            return false;
        }

        return mkdir($fullPath, 0755);
    }

    /**
     * Upload a file to the specified path.
     *
     * @param string $path The path to upload the file to.
     * @param array $file The file data array from the $_FILES superglobal.
     * @return array An array with success status and message.
     */
    public function uploadFile(string $path, array $file): array
    {
        $result = ["success" => false, "message" => ""];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $result["message"] = "Upload error: " . $file["error"];
            return $result;
        }

        if ($file["size"] > $this->maxFileSize) {
            $result["message"] = "File too large. Maximum size: " . $this->formatBytes($this->maxFileSize);
            return $result;
        }

        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $result["message"] = "File type not allowed. Allowed: " . implode(", ", $this->allowedExtensions);
            return $result;
        }

        $fullPath = $this->getFullPath($path . "/" . $file["name"]);
        if (!$this->isPathValid(dirname($fullPath))) {
            $result["message"] = "Invalid path";
            return $result;
        }

        if (move_uploaded_file($file["tmp_name"], $fullPath)) {
            $result["success"] = true;
            $result["message"] = "File uploaded successfully";
        } else {
            $result["message"] = "Failed to upload file";
        }

        return $result;
    }

    public function deleteItem(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        if (!$this->isPathValid($fullPath)) {
            return false;
        }

        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        } else {
            return unlink($fullPath);
        }
    }

    private function deleteDirectory(string $path): bool
    {
        $files = array_diff(scandir($path), [".", ".."]);
        foreach ($files as $file) {
            $filePath = "{$path}/{$file}";
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        return rmdir($path);
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;

        foreach (glob("{$path}/*") as $file) {
            $size += is_dir($file) ? $this->getDirectorySize($file) : filesize($file);
        }

        return $size;
    }

    public function formatBytes(int $bytes): string
    {
        $units = ["B", "KB", "MB", "GB"];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . " " . $units[$i];
    }

    public function getDirectFileUrl(string $path): string
    {
        return $this->baseUrl . ltrim($path, "/");
    }
}
