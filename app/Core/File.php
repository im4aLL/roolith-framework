<?php
namespace App\Core;

use App\Core\Interfaces\FileInterface;
use App\Utils\FS;
use Throwable;

/**
 * Uploaded file validator and mover with MIME plus storage hardening.
 *
 * Validates MIME via finfo (fallback mime_content_type) against an
 * allowlist of mime plus ext, rejects double extensions like shell.php.jpg
 * via an executable-segment deny list, and caps size by upload_max_filesize.
 * Generates random names via random_bytes (16 hex chars plus ext,
 * no user fragment) and writes a deny-execution .htaccess into the
 * destination so web-accessible dirs cannot run uploaded scripts. Prefer
 * storing outside the docroot; the .htaccess is defense in depth.
 */
class File implements FileInterface
{
    /**
     * Single-file $_FILES entry under validation.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $file = null;

    /**
     * Allowed lowercase extensions.
     *
     * @var array<int, string>
     */
    protected array $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];

    /**
     * Configured max size in bytes (capped by upload_max_filesize at check time).
     *
     * @var int
     */
    protected int $uploadSize;

    /**
     * Extension to allowed MIME types map.
     *
     * Fail-closed note (L9): docx/xlsx/pptx are exact-match only. When finfo
     * reports them as application/zip (common for minimal/renamed Office
     * files) they are rejected by design; callers should treat that as an
     * invalid upload, not as a zip.
     *
     * @var array<string, array<int, string>>
     */
    private const MIME_ALLOWLIST = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'zip' => ['application/zip'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    ];

    /**
     * Executable segments rejected in any non-final extension slot.
     *
     * @var array<int, string>
     */
    private const EXECUTABLE_SEGMENTS = [
        'php', 'phtml', 'phar', 'phps', 'php3', 'php4', 'php5', 'php7', 'php8',
        'cgi', 'pl', 'py', 'pyc', 'sh', 'exe', 'asp', 'aspx', 'jsp', 'htaccess',
    ];

    /**
     * Create a file validator with a 5MB default cap.
     */
    public function __construct()
    {
        $this->file = null;
        $this->uploadSize = 5 * 1024 * 1024;
    }

    /**
     * Set the file entry under validation.
     *
     * @param array<string, mixed> $name Single-file $_FILES entry.
     * @return static Self for chaining.
     */
    public function setFile(array $name): static
    {
        $this->file = $name;

        return $this;
    }

    /**
     * Check extension, MIME, upload error, and size together.
     *
     * @return bool True only when every check passes.
     */
    public function isValid(): bool
    {
        return $this->isValidExtension() &&
            $this->hasNoExecutableSegment() &&
            $this->isValidMime() &&
            $this->isValidFile() &&
            $this->isValidUploadSize();
    }

    /**
     * Check the final extension against the allowlist.
     *
     * @return bool True when the extension is allowlisted.
     */
    protected function isValidExtension(): bool
    {
        if (!is_array($this->file) || !isset($this->file['name']) || !is_string($this->file['name'])) {
            return false;
        }

        $extension = FS::getFileExtension($this->file['name']);

        return in_array($extension, $this->allowedFileTypes, true);
    }

    /**
     * Reject executable intermediate extensions (shell.php.jpg).
     *
     * Inspects every dot segment except the final extension; any segment
     * matching the executable deny list fails. Single-extension names pass.
     *
     * @return bool True when no executable segment is present.
     */
    protected function hasNoExecutableSegment(): bool
    {
        if (!is_array($this->file) || !isset($this->file['name']) || !is_string($this->file['name'])) {
            return false;
        }

        $base = strtolower(basename($this->file['name']));
        $parts = explode('.', $base);

        if (count($parts) < 3) {
            return true;
        }

        $intermediate = array_slice($parts, 0, -1);

        foreach ($intermediate as $segment) {
            if (in_array(trim($segment), self::EXECUTABLE_SEGMENTS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check the tmp file MIME via finfo against the extension allowlist.
     *
     * Uses finfo_open(FILEINFO_MIME_TYPE) with a mime_content_type fallback.
     * Missing tmp files, unreadable files, or unmapped extensions fail
     * closed. MIME comparison is exact after lowercasing and stripping
     * parameters (for example "; charset=binary").
     *
     * @return bool True when the detected MIME is allowlisted for the extension.
     */
    protected function isValidMime(): bool
    {
        if (!is_array($this->file) || !isset($this->file['name']) || !is_string($this->file['name'])) {
            return false;
        }

        $tmp = $this->file['tmp_name'] ?? null;

        if (!is_string($tmp) || $tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            return false;
        }

        $extension = FS::getFileExtension($this->file['name']);
        $allowed = self::MIME_ALLOWLIST[$extension] ?? null;

        if ($allowed === null) {
            return false;
        }

        $detected = self::detectMime($tmp);

        if ($detected === null) {
            return false;
        }

        return in_array($detected, $allowed, true);
    }

    /**
     * Detect the MIME type of a file without ever throwing.
     *
     * @param string $path Absolute tmp path to inspect.
     * @return string|null Lowercase MIME type or null when undetectable.
     */
    public static function detectMime(string $path): ?string
    {
        try {
            if (function_exists('finfo_open')) {
                $info = finfo_open(FILEINFO_MIME_TYPE);

                if ($info !== false) {
                    $mime = finfo_file($info, $path);
                    finfo_close($info);

                    if (is_string($mime) && $mime !== '') {
                        return strtolower(trim(explode(';', $mime)[0]));
                    }
                }
            }
        } catch (Throwable) {
            // Fall through to mime_content_type below.
        }

        try {
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($path);

                if (is_string($mime) && $mime !== '') {
                    return strtolower(trim(explode(';', $mime)[0]));
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Check size against the effective cap (configured capped by ini).
     *
     * @return bool True when the file size fits the effective max.
     */
    protected function isValidUploadSize(): bool
    {
        if (!is_array($this->file) || !isset($this->file['size']) || !is_numeric($this->file['size'])) {
            return false;
        }

        return self::effectiveMaxBytes($this->uploadSize) >= (int) $this->file['size'];
    }

    /**
     * Check the upload error flag.
     *
     * @return bool True when error is exactly 0 (or missing for BC test doubles).
     */
    protected function isValidFile(): bool
    {
        if (!is_array($this->file)) {
            return false;
        }

        if (isset($this->file['error'])) {
            return $this->file['error'] === 0;
        }

        return true;
    }

    /**
     * Move the upload to a directory with a validated name.
     *
     * Validates first so rejected uploads never create directories (M8), then
     * creates the directory plus deny-execution protection, then moves via
     * FS::upload(). A custom $name is strictly sanitized via
     * sanitizeCustomFilename() (basename, no separators, allowlisted ext plus
     * MIME plus executable-segment checks); invalid custom names fall back
     * to generateFileName() so traversal or shell.php can never escape.
     *
     * @param string $destinationPath Destination directory.
     * @param string|null $name Optional explicit filename (random when null or unsafe).
     * @return false|string Stored filename on success, false on failure.
     */
    public function upload(string $destinationPath, ?string $name = null): false|string
    {
        if (!$this->isValid()) {
            return false;
        }

        if (!FS::makeDirectory($destinationPath)) {
            return false;
        }

        FS::protectUploadDirectory($destinationPath);

        $filename = $this->resolveStoredFilename($name);
        $destination = rtrim($destinationPath, "/\\") . '/' . $filename;

        $tmp = is_array($this->file) ? ($this->file['tmp_name'] ?? null) : null;

        if (!is_string($tmp) || $tmp === '') {
            return false;
        }

        $isUploaded = FS::upload($tmp, $destination);

        if ($isUploaded) {
            return $filename;
        }

        return false;
    }

    /**
     * Resolve the stored filename, falling back to a random name on unsafe input.
     *
     * A null custom name always generates. A provided name is accepted only
     * via sanitizeCustomFilename(); anything rejected (traversal, absolute,
     * empty, disallowed ext, MIME mismatch, executable segment) falls back
     * to generateFileName() so the upload stays inside the destination with
     * a validated extension.
     *
     * @param string|null $name Custom filename or null for a random name.
     * @return string Safe stored filename.
     */
    private function resolveStoredFilename(?string $name): string
    {
        if ($name !== null) {
            $safe = $this->sanitizeCustomFilename($name);

            if ($safe !== null) {
                return $safe;
            }
        }

        return $this->generateFileName();
    }

    /**
     * Strictly sanitize a custom upload filename.
     *
     * Rejects empty names, NUL bytes, path separators (/ and \), parent
     * references (..), and bare dot names. Applies basename defense in depth,
     * then re-validates the final extension against the allowlist plus MIME
     * binding plus the executable-segment deny list. Returns null when any
     * check fails so the caller falls back to generateFileName().
     *
     * @param string $name Custom filename to sanitize.
     * @return string|null Safe basename or null when unsafe.
     */
    private function sanitizeCustomFilename(string $name): ?string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return null;
        }

        if (str_contains($trimmed, "\0") || str_contains($trimmed, '/') || str_contains($trimmed, '\\') || str_contains($trimmed, '..')) {
            return null;
        }

        $base = basename($trimmed);

        if ($base === '' || $base === '.' || $base === '..') {
            return null;
        }

        $extension = FS::getFileExtension($base);

        if ($extension === '' || !in_array($extension, $this->allowedFileTypes, true)) {
            return null;
        }

        $lower = strtolower($base);
        $parts = explode('.', $lower);

        if (count($parts) >= 3) {
            $intermediate = array_slice($parts, 0, -1);

            foreach ($intermediate as $segment) {
                if (in_array(trim($segment), self::EXECUTABLE_SEGMENTS, true)) {
                    return null;
                }
            }
        }

        $tmp = is_array($this->file) ? ($this->file['tmp_name'] ?? null) : null;

        if (!is_string($tmp) || $tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
            return null;
        }

        $allowed = self::MIME_ALLOWLIST[$extension] ?? null;

        if ($allowed === null) {
            return null;
        }

        $detected = self::detectMime($tmp);

        if ($detected === null || !in_array($detected, $allowed, true)) {
            return null;
        }

        return $base;
    }

    /**
     * Generate a random filename with no user fragment.
     *
     * 16 lowercase hex chars from random_bytes(8) plus the validated
     * lowercase extension (for example 3fa85c... .jpg). Falls back to
     * random_int hex when randomness fails.
     *
     * @return string Random filename with extension.
     */
    protected function generateFileName(): string
    {
        $extension = '';

        if (is_array($this->file) && isset($this->file['name']) && is_string($this->file['name'])) {
            $extension = FS::getFileExtension($this->file['name']);
        }

        try {
            $random = bin2hex(random_bytes(8));
        } catch (Throwable) {
            $random = sprintf('%04x%04x%04x%04x', random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff));
        }

        return $extension !== '' ? $random . '.' . $extension : $random;
    }

    /**
     * Set allowed extensions (MIME map still governs per-ext MIME).
     *
     * @param array<int, string> $types Allowed lowercase extensions.
     * @return static Self for chaining.
     */
    public function setAllowedFileTypes(array $types): static
    {
        $this->allowedFileTypes = array_values(array_map(static fn ($t): string => strtolower((string) $t), $types));

        return $this;
    }

    /**
     * Set the max upload size in megabytes (capped by upload_max_filesize).
     *
     * @param int|float $size Max size in megabytes.
     * @return static Self for chaining.
     */
    public function setMaxUploadSize(int|float $size): static
    {
        $this->uploadSize = (int) ((float) $size * 1024 * 1024);

        return $this;
    }

    /**
     * Get the effective max bytes (configured capped by php.ini).
     *
     * @param int $configuredBytes Configured max in bytes.
     * @return int Effective max in bytes.
     */
    public static function effectiveMaxBytes(int $configuredBytes): int
    {
        $iniMax = self::parseIniSize((string) ini_get('upload_max_filesize'));

        if ($iniMax !== null && $iniMax < $configuredBytes) {
            return $iniMax;
        }

        return $configuredBytes;
    }

    /**
     * Parse a php.ini size string (2M, 512K, 1G) to bytes.
     *
     * @param string $raw Ini value to parse.
     * @return int|null Bytes or null when unparsable.
     */
    public static function parseIniSize(string $raw): ?int
    {
        $trimmed = trim($raw);

        if ($trimmed === '' || $trimmed === '-1') {
            return null;
        }

        if (is_numeric($trimmed)) {
            return (int) $trimmed;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = substr($trimmed, 0, -1);

        if (!is_numeric($number)) {
            return null;
        }

        $value = (float) $number;

        return match ($unit) {
            'g' => (int) ($value * 1024 * 1024 * 1024),
            'm' => (int) ($value * 1024 * 1024),
            'k' => (int) ($value * 1024),
            default => null,
        };
    }
}
