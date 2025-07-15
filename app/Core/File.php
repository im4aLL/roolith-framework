<?php
namespace App\Core;


use App\Core\Interfaces\FileInterface;
use App\Utils\FS;

class File implements FileInterface
{
    protected $file;
    protected $allowedFileTypes;
    protected $uploadSize;

    public function __construct()
    {
        $this->file = null;
        $this->allowedFileTypes = ['jpg', 'jpeg', 'png'];
        $this->uploadSize = 2097152; // 2 * 1024 * 1024 = 2 MB
    }

    /**
     * @inheritDoc
     */
    public function setFile($name): File|FileInterface|static
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
        if (FS::makeDirectory($destinationPath)) {
            if ($this->isValid()) {
                $filename = $name ? $name : $this->generateFileName();
                $destination = $destinationPath . '/' . $filename;

                $isUploaded = FS::upload($this->file['tmp_name'], $destination);

                if ($isUploaded) {
                    return $filename;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Generate file name
     *
     * @return string
     */
    protected function generateFileName(): string
    {
        return time().'_'.preg_replace("/[^a-zA-Z0-9-_.]+/", "", $this->file['name']);
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
