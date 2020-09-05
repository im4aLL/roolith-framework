<?php
namespace App\Core\Interfaces;


interface FileInterface
{
    /**
     * Set file by name
     *
     * @param $name
     * @return $this
     */
    public function setFile($name);

    /**
     * If valid file type
     *
     * @return bool
     */
    public function isValid();

    /**
     * Upload file to a path
     *
     * @param $destinationPath
     * @param $name
     * @return false|string
     */
    public function upload($destinationPath, $name = null);

    /**
     * Set allowed file types
     *
     * @param $types array
     * @return $this
     */
    public function setAllowedFileTypes($types);

    /**
     * Set max upload size
     *
     * @param $size
     * @return $this
     */
    public function setMaxUploadSize($size);
}