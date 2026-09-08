<?php
namespace App\Support;

use App\Core\RouterFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Throwable;

/**
 * URL building helpers.
 *
 * Backs the global url(), route(), and getActiveRoute() functions.
 * Keeps slash normalization and fail-closed baseUrl handling in one
 * place so globals stay thin BC aliases.
 */
final class Url
{
    /**
     * Prefix the app base URL to a path with slash normalization.
     *
     * Joins as rtrim(base, '/') . '/' . ltrim(path, '/') so both
     * baseUrl with/without trailing slash and paths with/without leading
     * slash produce one slash. When baseUrl is missing or empty the
     * misconfiguration is logged and in development throws so it fails
     * fast; in production it falls back to a root-relative path.
     *
     * @param string $path Path to prefix (for example assets/css/app.css or /assets/css/app.css).
     * @return string Absolute URL when baseUrl exists, otherwise a root-relative path.
     * @throws InvalidArgumentException When baseUrl is missing and APP_ENV is development.
     */
    public static function to(string $path): string
    {
        $fallback = '/' . ltrim($path, '/');

        try {
            $baseUrl = Config::get('baseUrl');
        } catch (InvalidArgumentException $e) {
            self::logMisconfiguration('Missing baseUrl config: ' . $e->getMessage());

            if (self::isDev()) {
                throw new InvalidArgumentException('Missing baseUrl config: ' . $e->getMessage(), 0, $e);
            }

            return $fallback;
        } catch (Throwable $e) {
            self::logMisconfiguration('Cannot read baseUrl config: ' . $e->getMessage());

            if (self::isDev()) {
                throw new InvalidArgumentException('Missing baseUrl config: ' . $e->getMessage(), 0, $e);
            }

            return $fallback;
        }

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            self::logMisconfiguration('Missing baseUrl config: empty value.');

            if (self::isDev()) {
                throw new InvalidArgumentException('Missing baseUrl config: empty value.');
            }

            return $fallback;
        }

        $base = rtrim(trim($baseUrl), '/');
        $suffix = ltrim($path, '/');

        if ($suffix === '') {
            return $base . '/';
        }

        return $base . '/' . $suffix;
    }

    /**
     * Get URL by router name.
     *
     * Thin proxy over the shared router so reverse routing stays
     * centralized. Never throws for missing names; the vendor router
     * returns the base URL unchanged.
     *
     * @param string $name Route name.
     * @param array<string, mixed> $settings Route params keyed by placeholder.
     * @return string Absolute URL for the named route.
     */
    public static function route(string $name, array $settings = []): string
    {
        $routerInstance = RouterFactory::getInstance();

        return $routerInstance->getUrlByName($name, $settings);
    }

    /**
     * Get active route.
     *
     * Returns the matched route with payload, or an empty array when
     * nothing matches (the router returns null on no-match).
     *
     * @return array<string, mixed> Active route data or empty array.
     */
    public static function activeRoute(): array
    {
        $routerInstance = RouterFactory::getInstance();

        try {
            return $routerInstance->activeRoute();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Check whether the current environment is development.
     *
     * Local seam so URL helpers fail fast in dev without depending on
     * the global isDevEnvironment() function (which now delegates here
     * in reverse). Fail-closed: only explicit development returns true.
     *
     * @return bool True in development, false otherwise.
     */
    private static function isDev(): bool
    {
        return \App\Core\Env::isDevelopment();
    }

    /**
     * Log a URL misconfiguration without ever throwing.
     *
     * Prefers the shared PSR-3 logger when booted, falls back to
     * error_log so early-boot URL building still leaves a trail.
     *
     * @param string $message Log message.
     * @return void
     */
    private static function logMisconfiguration(string $message): void
    {
        try {
            \App\Core\Log::warning('[Roolith url] ' . $message);
        } catch (Throwable) {
            // Fall through to error_log below.
        }

        error_log('[Roolith url] ' . $message);
    }
}
