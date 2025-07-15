<?php
namespace App\Core;

use App\Core\Interfaces\FileInterface;
use App\Utils\FS;
use Random\RandomException;

class File implements FileInterface
{
    protected $file;
    protected array $allowedFileTypes;
    protected int|float $uploadSize;

    public function __construct()
    {
        $this->file = null;
        $this->allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];
        $this->uploadSize = 5 * 1024 * 1024; // 5mb
    }

    /**
     * @inheritDoc
     */
    public function setFile($name): static
    {
        $this->file = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return $this->isValidExtension() &&
            $this->isValidFile() &&
            $this->isValidUploadSize();
    }

    /**
     * Is valid extension
     *
     * @return bool
     */
    protected function isValidExtension(): bool
    {
        $extension = FS::getFileExtension($this->file['name']);

        return in_array($extension, $this->allowedFileTypes);
    }

    /**
     * Is valid upload size
     *
     * @return bool
     */
    protected function isValidUploadSize(): bool
    {
        return $this->uploadSize >= $this->file['size'];
    }

    /**
     * Is a valid file
     *
     * @return bool
     */
    protected function isValidFile(): bool
    {
        if (isset($this->file['error'])) {
            return $this->file['error'] === 0;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function upload($destinationPath, $name = null)
    {
        if (!FS::makeDirectory($destinationPath)) {
            return false;
        }

        if (!$this->isValid()) {
            return false;
        }

        $filename = $name ?: $this->generateFileName();
        $destination = $destinationPath . '/' . $filename;

        $isUploaded = FS::upload($this->file['tmp_name'], $destination);

        if ($isUploaded) {
            return $filename;
        }

        return false;
    }

    /**
     * Generate file name
     *
     * @return string
     * @throws RandomException
     */
    protected function generateFileName(): string
    {
        return time().'_'.random_int(100000, 999999).'_'.preg_replace("/[^a-zA-Z0-9-_.]+/", "", $this->file['name']);
    }

    /**
     * @inheritDoc
     */
    public function setAllowedFileTypes($types): File|FileInterface|static
    {
        $this->allowedFileTypes = $types;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setMaxUploadSize($size): File|FileInterface|static
    {
        $this->uploadSize = $size * 1024 * 1024;

        return $this;
    }
}
