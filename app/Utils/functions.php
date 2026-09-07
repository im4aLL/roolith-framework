<?php

use App\Utils\Str;
use Carbon\Carbon;
use App\Core\RouterFactory;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;

/**
 * Print anything
 *
 * @param $any
 * @param bool $exit
 */
function p($any, bool $exit = false): void
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
 * @param $path
 * @return string
 */
function url($path): string
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
 * @param $name
 * @param array $settings
 * @return string
 */
function route($name, array $settings = []): string
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
 * @param $name
 * @return mixed|null
 */
function __($name): mixed
{
    return Str::getMessage($name);
}

/**
 * Redirect to URL
 *
 * @param string $url
 * @param integer $statusCode
 * @return void
 */
function redirect(string $url, int $statusCode = 303): void
{
    header("Location: {$url}", true, $statusCode);

    die();
}

/**
 * Redirect to route name
 *
 * @param string $routeName
 * @param array $settings
 * @param integer $statusCode
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
 * @return bool
 */
function isDevEnvironment(): bool
{
    if (!defined("ROOLITH_ENV")) {
        return true;
    }

    return ROOLITH_ENV == "development";
}

/**
 * Is production environment
 *
 * @return bool
 */
function isProductionEnvironment(): bool
{
    if (!defined("ROOLITH_ENV")) {
        return false;
    }

    return ROOLITH_ENV == "production";
}

/**
 * Get user IP address
 *
 * @return mixed|string
 */
function getIpAddress(): mixed
{
    $ipAddress = "127.0.0.1";

    if (isset($_SERVER["HTTP_CLIENT_IP"])) {
        $ipAddress = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ipAddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
        $ipAddress = $_SERVER["HTTP_X_FORWARDED"];
    } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
        $ipAddress = $_SERVER["HTTP_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_FORWARDED"])) {
        $ipAddress = $_SERVER["HTTP_FORWARDED"];
    } elseif (isset($_SERVER["REMOTE_ADDR"])) {
        $ipAddress = $_SERVER["REMOTE_ADDR"];
    }

    return $ipAddress;
}

/**
 * Parse basic template
 *
 * @param $string
 * @param array $data
 * @return string|string[]|null
 */
function parseBasicTemplate($string, array $data = []): array|string|null
{
    $findArray = [];
    $replaceArray = [];

    foreach ($data as $key => $value) {
        $findArray[] = "/{{$key}}/";
        $replaceArray[] = $value;
    }

    return preg_replace($findArray, $replaceArray, $string);
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
