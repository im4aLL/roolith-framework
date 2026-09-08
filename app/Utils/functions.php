<?php

use App\Utils\Str;
use Carbon\Carbon;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;

/**
 * Print anything
 *
 * @param mixed $any Value to print.
 * @param bool $exit
 */
function p(mixed $any, bool $exit = false): void
{
    echo "<pre>";
    print_r($any);
    echo "</pre>";

    if ($exit) {
        die();
    }
}

/**
 * Prefix app url in a path
 *
 * @param string $path Path to prefix.
 * @return string
 */
function url(string $path): string
{
    try {
        return Config::get("baseUrl") . $path;
    } catch (InvalidArgumentException $e) {
        return $path;
    }
}

/**
 * Get vite dev server url from configuration
 *
 * Empty means vite dev server is not used
 *
 * @return string
 */
function viteDevServerUrl(): string
{
    try {
        return rtrim((string) Config::get("viteDevServer"), "/");
    } catch (InvalidArgumentException $e) {
        return "";
    }
}

/**
 * Get a built asset url with version query
 *
 * @param string $path e.g. assets/css/app.css
 * @return string
 */
function viteBuiltAssetUrl(string $path): string
{
    return url($path . "?v=" . getVersion());
}

/**
 * Render the vite client tag once per page
 *
 * @param string $devServer
 * @return string
 */
function viteClientTag(string $devServer): string
{
    static $rendered = false;

    if ($rendered) {
        return "";
    }

    $rendered = true;

    return "<script type=\"module\" src=\"{$devServer}/@vite/client\"></script>";
}

/**
 * Render a stylesheet tag for a vite entry
 *
 * @param string $sourcePath e.g. source/scss/app.scss
 * @param string $builtPath e.g. assets/css/app.css
 * @return string
 */
function viteCss(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        return viteClientTag($devServer) .
            "<link rel=\"stylesheet\" href=\"{$devServer}/{$sourcePath}\">";
    }

    return "<link rel=\"stylesheet\" href=\"" . viteBuiltAssetUrl($builtPath) . "\">";
}

/**
 * Render a script tag for a vite entry
 *
 * @param string $sourcePath e.g. source/js/app.js
 * @param string $builtPath e.g. assets/js/app.js
 * @return string
 */
function viteJs(string $sourcePath, string $builtPath): string
{
    $devServer = viteDevServerUrl();

    if (isDevEnvironment() && $devServer !== "") {
        return "<script type=\"module\" src=\"{$devServer}/{$sourcePath}\"></script>";
    }

    return "<script src=\"" . viteBuiltAssetUrl($builtPath) . "\"></script>";
}

/**
 * Get url by router name
 *
 * @param string $name Route name.
 * @param array $settings
 * @return string
 */
function route(string $name, array $settings = []): string
{
    $routerInstance = RouterFactory::getInstance();

    return $routerInstance->getUrlByName($name, $settings);
}

/**
 * Get active route
 *
 * Returns the matched route with payload, or an empty array when
 * nothing matches (the router returns null on no-match).
 *
 * @return array
 */
function getActiveRoute(): array
{
    $routerInstance = RouterFactory::getInstance();

    try {
        return $routerInstance->activeRoute() ?? [];
    } catch (\Throwable) {
        return [];
    }
}

/**
 * Get a message
 *
 * @param string $name Message key.
 * @return mixed|null
 */
function __(string $name): mixed
{
    return Str::getMessage($name);
}

/**
 * Redirect to URL
 *
 * Status is deliberate: 303 (See Other) is the default because it implements
 * Post/Redirect/Get safely by always following up with GET. Pass 302 for a
 * temporary redirect where legacy behavior is needed, or 301/308 for a
 * permanent redirect (308 preserves the method, 301 may not).
 *
 * Only single-slash relative URLs or absolute URLs allowlisted against the
 * base URL are sent; anything else (for example ?next=https://evil.com,
 * //evil.com, javascript:) falls back to / to block open redirects.
 *
 * @param string $url Redirect target.
 * @param integer $statusCode HTTP redirect code, 303 by default.
 * @return void
 */
function redirect(string $url, int $statusCode = 303): void
{
    $target = \App\Core\PreProcessor::resolveSafeRedirectTarget($url);

    header("Location: {$target}", true, $statusCode);

    die();
}

/**
 * Redirect to route name
 *
 * Uses 303 by default for the same Post/Redirect/Get reason as redirect().
 * Pass an explicit code when a different redirect semantic is intended.
 *
 * @param string $routeName Route name.
 * @param array $settings Route params.
 * @param integer $statusCode HTTP redirect code, 303 by default.
 * @return void
 */
function redirectToRoute(string $routeName, array $settings = [], int $statusCode = 303): void
{
    $url = route($routeName, $settings);

    redirect($url, $statusCode);
}

/**
 * Generate unique alpha numeric number
 *
 * @return string
 */
function generateUniqueAlphaNumericNumber(): string
{
    $prefix = substr(str_shuffle(str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4)), 0, 4);
    $postfix = time();

    return "{$prefix}-{$postfix}";
}

/**
 * Generate unique number
 *
 * @return string
 */
function generateUniqueNumber(): string
{
    return substr(str_shuffle(str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4)), 0, 2) .
        "-" .
        mt_rand(100000, 999999) .
        "-" .
        time();
}

/**
 * Get Current date and time
 *
 * @return string
 */
function getCurrentDateTime(): string
{
    return Carbon::now()->toDateTimeString();
}

/**
 * Get today's date
 *
 * @return string
 */
function getCurrentDate(): string
{
    return Carbon::now()->toDateString();
}

/**
 * Is dev environment
 *
 * Single source is APP_ENV via App\Core\Env. Fail-closed: only an explicit
 * APP_ENV=development returns true.
 *
 * @return bool
 */
function isDevEnvironment(): bool
{
    return \App\Core\Env::isDevelopment();
}

/**
 * Is production environment
 *
 * Fail-closed: anything that is not development counts as production-safe.
 *
 * @return bool
 */
function isProductionEnvironment(): bool
{
    return \App\Core\Env::isProduction();
}

/**
 * List proxy IPs allowed to set forwarded-IP headers.
 *
 * Single source is config trustedProxies (mapped from TRUSTED_PROXIES).
 * Empty means proxy headers are never trusted. Only exact IP matches are
 * honored (CIDR ranges are not supported and are dropped); invalid IPs are
 * filtered out. Never throws so IP resolution stays available before
 * config boots.
 *
 * @return array<int, string> Trusted proxy IP strings (valid IPs only).
 */
function trustedProxies(): array
{
    try {
        $configured = Config::get('trustedProxies');
    } catch (\Throwable) {
        $configured = null;
    }

    if (is_array($configured)) {
        $proxies = [];

        foreach ($configured as $proxy) {
            if (is_string($proxy)) {
                $proxy = trim($proxy);

                if ($proxy !== '' && filter_var($proxy, FILTER_VALIDATE_IP) !== false) {
                    $proxies[] = $proxy;
                }
            }
        }

        return $proxies;
    }

    $raw = \App\Core\Env::get('TRUSTED_PROXIES', '');

    if ($raw === null || trim($raw) === '') {
        return [];
    }

    $proxies = [];

    foreach (explode(',', $raw) as $proxy) {
        $proxy = trim($proxy);

        if ($proxy !== '' && filter_var($proxy, FILTER_VALIDATE_IP) !== false) {
            $proxies[] = $proxy;
        }
    }

    return $proxies;
}

/**
 * Get user IP address
 *
 * Fail-closed: proxy headers (X-Forwarded-For and friends) are only
 * honored when REMOTE_ADDR itself is an exact match in trustedProxies().
 * Otherwise REMOTE_ADDR is returned so clients cannot spoof rate
 * limiting or logs. Forwarded lists resolve to the first valid IP.
 * CIDR ranges are not supported; list individual proxy IPs.
 *
 * @return string Client IP address.
 */
function getIpAddress(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $remote = is_string($remote) ? trim($remote) : '127.0.0.1';

    if (filter_var($remote, FILTER_VALIDATE_IP) === false) {
        $remote = '127.0.0.1';
    }

    if (!in_array($remote, trustedProxies(), true)) {
        return $remote;
    }

    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'];

    foreach ($headers as $header) {
        if (!isset($_SERVER[$header]) || !is_string($_SERVER[$header])) {
            continue;
        }

        $first = trim(explode(',', $_SERVER[$header])[0] ?? '');

        if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false) {
            return $first;
        }
    }

    return $remote;
}

/**
 * Parse basic template
 *
 * Replaces {{key}} placeholders with string casts of the given values
 * using literal string replacement (no regex) so keys with regex chars
 * cannot break or inject patterns.
 *
 * @param string|array<string> $string Template string or strings.
 * @param array<string, mixed> $data Placeholder values keyed by name.
 * @return string|string[]|null Replaced template(s).
 */
function parseBasicTemplate(string|array $string, array $data = []): array|string|null
{
    $findArray = [];
    $replaceArray = [];

    foreach ($data as $key => $value) {
        $findArray[] = '{{' . (string) $key . '}}';
        $replaceArray[] = (string) $value;
    }

    if ($findArray === []) {
        return $string;
    }

    return str_replace($findArray, $replaceArray, $string);
}

/**
 * Get a version
 *
 * @return string
 * @throws InvalidArgumentException
 */
function getVersion(): string
{
    return Config::get("version");
}

/**
 * CMS related routes
 */
if (APP_ENABLE_CMS) {
    require_once APP_ROOT . "/app/Utils/Admin/functions.php";
}
