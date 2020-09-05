<?php
namespace App\Core;


use App\Core\Interfaces\FileInterface;

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
    public function setFile($name)
    {
        $this->file = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isValid()
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
    protected function isValidExtension()
    {
        $extension = $this->getFileExtension($this->file['name']);

        return in_array($extension, $this->allowedFileTypes);
    }

    /**
     * Is valid upload size
     *
     * @return bool
     */
    protected function isValidUploadSize()
    {
        return $this->uploadSize >= $this->file['size'];
    }

    /**
     * Is valid file
     *
     * @return bool
     */
    protected function isValidFile()
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
        if ($this->makeDirectory($destinationPath)) {
            if ($this->isValid()) {
                $filename = $name ? $name : $this->generateFileName();
                $destination = $destinationPath . '/' . $filename;

                $isUploaded = move_uploaded_file($this->file['tmp_name'], $destination);

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
    protected function generateFileName()
    {
        return time().'_'.preg_replace("/[^a-zA-Z0-9-_.]+/", "", $this->file['name']);
    }

    /**
     * @inheritDoc
     */
    public function setAllowedFileTypes($types)
    {
        $this->allowedFileTypes = $types;

        return $this;
    }

    /**
     * Get file extension
     *
     * @param $filename
     * @return string
     */
    protected function getFileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Create directory if not exists
     *
     * @param $path
     * @return bool
     */
    protected function makeDirectory($path)
    {
        if (!is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function setMaxUploadSize($size)
    {
        $this->uploadSize = $size * 1024 * 1024;

        return $this;
    }
}