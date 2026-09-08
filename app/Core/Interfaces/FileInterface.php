<?php
namespace App\Core\Interfaces;


interface FileInterface
{
    /**
     * Set file by name
     *
     * @param array<string, mixed> $name Single-file $_FILES entry.
     * @return static Self for chaining.
     */
    public function setFile(array $name): static;

    /**
     * If valid file type
     *
     * @return bool True when extension, MIME, size, and upload error all pass.
     */
    public function isValid(): bool;

    /**
     * Upload file to a path
     *
     * Custom names are sanitized (no separators, allowlisted ext plus MIME);
     * unsafe names fall back to a random name so uploads stay in place.
     *
     * @param string $destinationPath Destination directory.
     * @param string|null $name Optional explicit filename (random when null or unsafe).
     * @return false|string Stored filename on success, false on failure.
     */
    public function upload(string $destinationPath, ?string $name = null): false|string;

    /**
     * Set allowed file types
     *
     * @param array<int, string> $types Allowed lowercase extensions.
     * @return static Self for chaining.
     */
    public function setAllowedFileTypes(array $types): static;

    /**
     * Set max upload size
     *
     * @param int|float $size Max size in megabytes, capped by upload_max_filesize.
     * @return static Self for chaining.
     */
    public function setMaxUploadSize(int|float $size): static;
}