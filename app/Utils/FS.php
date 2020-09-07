<?php
namespace App\Utils;


class FS
{
    /**
     * Upload a file
     *
     * @param $file
     * @param $destination
     * @return bool
     */
    public static function upload($file, $destination)
    {
        return move_uploaded_file($file, $destination);
    }

    /**
     * Get file extension
     *
     * @param $filename
     * @return string
     */
    public static function getFileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Make directory
     *
     * @param $path
     * @param int $permission
     * @return bool
     */
    public static function makeDirectory($path, $permission = 0777)
    {
        if (!is_dir($path)) {
            return mkdir($path, $permission, true);
        }

        return false;
    }

    /**
     * Remove a directory and all files within it
     *
     * @param $path
     * @return bool
     */
    public static function removeDirectory($path)
    {
        if (substr($path, strlen($path) - 1, 1) !== '/') {
            $path .= '/';
        }

        $files = glob($path . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::removeDirectory($file);
            } else {
                unlink($file);
            }
        }

        return rmdir($path);
    }

    /**
     * Remove a file
     *
     * @param $path
     * @return bool
     */
    public static function removeFile($path)
    {
        if (self::exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Delete files in a directory
     *
     * @param $path
     * @return bool
     */
    public static function removeFilesInDirectory($path)
    {
        $result = true;
        $files = glob($path.'/*');

        foreach ($files as $file) {
            try {
                unlink($file);
            } catch (Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * If file exists
     *
     * @param $path
     * @return bool
     */
    public static function exists($path)
    {
        return file_exists($path);
    }
}
