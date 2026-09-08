<?php
namespace App\Utils;

/**
 * Filesystem helpers with deletion guards.
 *
 * removeDirectory(), removeFilesInDirectory(), and removeFile() all refuse
 * empty paths, filesystem root, and APP_ROOT via assertDeletablePath() so
 * a bad variable can never wipe the project or disk root.
 */
class FS
{
    /**
     * Upload a file via HTTP POST.
     *
     * Uses move_uploaded_file in production. Falls back to rename plus copy
     * for CLI and test doubles where is_uploaded_file is false but the file
     * exists, so unit tests can exercise validation without a real SAPI.
     *
     * @param string $file Source tmp path.
     * @param string $destination Destination path.
     * @return bool True on success.
     */
    public static function upload(string $file, string $destination): bool
    {
        if (is_uploaded_file($file)) {
            return move_uploaded_file($file, $destination);
        }

        if (PHP_SAPI === 'cli' && is_file($file) && is_readable($file)) {
            $destDir = dirname($destination);

            try {
                // Pre-checked for readability/writability so failures return
                // false instead of relying on warning suppression.
                if (is_dir($destDir) && is_writable($destDir) && rename($file, $destination)) {
                    return true;
                }

                if (is_file($file) && is_dir($destDir) && is_writable($destDir) && copy($file, $destination)) {
                    return true;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        return move_uploaded_file($file, $destination);
    }

    /**
     * Write deny-execution protection into an upload directory.
     *
     * Prefer storing outside the docroot; this .htaccess is defense in depth
     * for web-accessible dirs. Existing files are left untouched so deployer
     * customizations survive.
     *
     * @param string $directory Upload directory to protect.
     * @return bool True when the directory is protected or protection was written.
     */
    public static function protectUploadDirectory(string $directory): bool
    {
        try {
            if (!is_dir($directory) || !is_writable($directory)) {
                return false;
            }

            $htaccess = rtrim($directory, "/\\") . DIRECTORY_SEPARATOR . '.htaccess';

            if (is_file($htaccess)) {
                return true;
            }

            $contents = "# Roolith upload protection.\n"
                . "# Prefer storing outside the docroot; this denies script execution\n"
                . "# when the directory is web-accessible.\n"
                . "php_flag engine off\n"
                . "RemoveHandler .php .phtml .phar\n"
                . "RemoveType .php .phtml .phar\n"
                . "<FilesMatch \"\\.(php|phtml|phar|php[0-9]+|htaccess)$\">\n"
                . "    Require all denied\n"
                . "</FilesMatch>\n";

            $written = file_put_contents($htaccess, $contents);

            return $written !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get the lowercase file extension without the dot.
     *
     * @param string $filename File name to inspect.
     * @return string Lowercase extension or empty string when none.
     */
    public static function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Create a directory recursively when missing.
     *
     * @param string $path Directory path to create.
     * @param int $permission Permissions for created directories.
     * @return bool True when the directory exists after the call.
     */
    public static function makeDirectory(string $path, int $permission = 0777): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $permission, true);
        }

        return true;
    }

    /**
     * Remove a directory and all files within it.
     *
     * Refuses empty paths, filesystem root (/), and APP_ROOT itself to
     * prevent deletion mistakes. Handles dotfiles via scandir (glob misses
     * them) and unlinks symlinks instead of recursing into them. Returns
     * false when any entry or the final rmdir fails instead of swallowing
     * errors.
     *
     * @param string $path Directory to remove recursively.
     * @return bool True when the directory was fully removed.
     * @throws \InvalidArgumentException When the path is refused (empty, /, or APP_ROOT).
     */
    public static function removeDirectory(string $path): bool
    {
        self::assertDeletablePath($path);

        if (!is_dir($path) || is_link($path)) {
            return false;
        }

        $entries = scandir($path);

        if ($entries === false) {
            return false;
        }

        $ok = true;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $entry;

            try {
                if (is_link($full)) {
                    if (!unlink($full)) {
                        $ok = false;
                    }
                } elseif (is_dir($full)) {
                    if (!self::removeDirectory($full)) {
                        $ok = false;
                    }
                } else {
                    if (!unlink($full)) {
                        $ok = false;
                    }
                }
            } catch (\Throwable) {
                $ok = false;
            }
        }

        try {
            if (!rmdir($path)) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return $ok;
    }

    /**
     * Remove a single file.
     *
     * Refuses empty paths, filesystem root (/), and APP_ROOT itself via
     * assertDeletablePath() to match removeDirectory() hardening. Returns
     * false when no file exists; never throws for a missing file.
     *
     * @param string $path File path to remove.
     * @return bool True when a file was removed.
     * @throws \InvalidArgumentException When the path is refused (empty, /, or APP_ROOT).
     */
    public static function removeFile(string $path): bool
    {
        self::assertDeletablePath($path);

        if (self::exists($path)) {
            if (is_dir($path) && !is_link($path)) {
                return false;
            }

            try {
                return unlink($path);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    /**
     * Delete top-level files in a directory (keeps subdirectories).
     *
     * Refuses empty paths, filesystem root (/), and APP_ROOT itself.
     * Handles dotfiles via scandir and unlinks symlinked files instead of
     * following them. Subdirectories are skipped. Returns false when any
     * unlink fails.
     *
     * @param string $path Directory whose top-level files to delete.
     * @return bool True when all top-level files were removed.
     * @throws \InvalidArgumentException When the path is refused (empty, /, or APP_ROOT).
     */
    public static function removeFilesInDirectory(string $path): bool
    {
        self::assertDeletablePath($path);

        if (!is_dir($path) || is_link($path)) {
            return false;
        }

        $entries = scandir($path);

        if ($entries === false) {
            return false;
        }

        $result = true;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($full) && !is_link($full)) {
                continue;
            }

            try {
                if (!unlink($full)) {
                    $result = false;
                }
            } catch (\Throwable) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Check whether a path exists.
     *
     * @param string $path Path to check.
     * @return bool True when the path exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Refuse dangerous deletion targets.
     *
     * Throws for empty/whitespace paths, filesystem root (/ or \), and
     * APP_ROOT itself (compared via realpath when available, plus a
     * normalized string fallback). Never throws for ordinary temp paths.
     *
     * @param string $path Candidate directory for deletion.
     * @return void
     * @throws \InvalidArgumentException When the path must not be deleted.
     */
    private static function assertDeletablePath(string $path): void
    {
        if (trim($path) === '') {
            throw new \InvalidArgumentException("Refusing to delete: path is empty.");
        }

        $normalized = rtrim(trim($path), "/\\");

        if ($normalized === '' || $normalized === '.' || preg_match('#^[A-Za-z]:$#', $normalized) === 1) {
            throw new \InvalidArgumentException("Refusing to delete filesystem root '{$path}'.");
        }

        $real = realpath($path);

        if ($real !== false) {
            $realNormalized = rtrim($real, "/\\");

            if ($realNormalized === '' || $realNormalized === '/' || preg_match('#^[A-Za-z]:\\\\?$#', $realNormalized) === 1) {
                throw new \InvalidArgumentException("Refusing to delete filesystem root '{$path}'.");
            }

            if (defined('APP_ROOT')) {
                $appRoot = realpath((string) APP_ROOT);

                if ($appRoot !== false && rtrim($appRoot, "/\\") === $realNormalized) {
                    throw new \InvalidArgumentException("Refusing to delete APP_ROOT '{$path}'.");
                }
            }

            return;
        }

        if (defined('APP_ROOT')) {
            $appRootRaw = rtrim(trim((string) APP_ROOT), "/\\");
            $candidateRaw = rtrim($normalized, "/\\");

            if ($candidateRaw !== '' && $candidateRaw === $appRootRaw) {
                throw new \InvalidArgumentException("Refusing to delete APP_ROOT '{$path}'.");
            }
        }
    }
}
