<?php
namespace App\Core\Interfaces;


interface RequestInterface
{
    /**
     * Get request input
     * If there is no input but default value pass then return default
     *
     * @param string $name Input key.
     * @param mixed $default Fallback when missing.
     * @return mixed Sanitized value or the default.
     */
    public static function input(string $name, mixed $default = null): mixed;

    /**
     * Whether has not input or not
     *
     * @param string $name Input key.
     * @return bool True when present.
     */
    public static function has(string $name): bool;

    /**
     * Get all input
     *
     * @param array<string, mixed> $settings Optional flags.
     * @return iterable<string, mixed> All inputs.
     */
    public static function all(array $settings = []): iterable;

    /**
     * Get only specified array
     *
     * @param string|array<int|string, mixed> $name Key or keys to keep.
     * @return array<string, mixed> Filtered inputs.
     */
    public static function only(string|array $name): array;

    /**
     * Get all input except provided
     *
     * @param string|array<int|string, mixed> $name Key or keys to drop.
     * @return array<string, mixed> Remaining inputs.
     */
    public static function except(string|array $name): array;

    /**
     * Redirect to given url
     *
     * Only single-slash relative URLs or allowlisted absolute URLs are sent;
     * anything else falls back to / to block open redirects.
     *
     * @param string $url Redirect target.
     * @return void
     */
    public static function redirect(string $url): void;

    /**
     * Get cookie by name
     *
     * @param string $name Cookie name.
     * @return mixed Cookie value or null when missing.
     */
    public static function cookie(string $name): mixed;

    /**
     * Get file in request
     *
     * @param string $name File input name.
     * @param array<string, mixed>|null $fileData Single-file chunk override.
     * @return false|FileInterface File wrapper or false when missing.
     */
    public static function file(string $name, ?array $fileData = null): bool|FileInterface;

    /**
     * If request has file by name
     *
     * @param string $name File input name.
     * @return bool True when present.
     */
    public static function hasFile(string $name): bool;

    /**
     * If request type is ajax
     *
     * @return bool True for XHR requests.
     */
    public static function ajax(): bool;

    /**
     * Get request url
     *
     * @return bool|string URL without query or false when unavailable.
     */
    public static function url(): bool|string;

    /**
     * Get full url with query param
     *
     * @return string Absolute URL.
     */
    public static function fullUrl(): string;

    /**
     * Get request method name
     *
     * @return string Method name, GET when unavailable.
     */
    public static function method(): string;

    /**
     * Check whether request method name match
     *
     * @param string $methodName Method name to compare.
     * @return bool True on match.
     */
    public static function isMethod(string $methodName): bool;
}
