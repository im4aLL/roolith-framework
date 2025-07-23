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
    echo '<pre>';
    print_r($any);
    echo '</pre>';

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
        return Config::get('baseUrl') . $path;
    } catch (InvalidArgumentException $e) {
        return $path;
    }
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
 * @return array
 */
function getActiveRoute(): array
{
    $routerInstance = RouterFactory::getInstance();

    return $routerInstance->activeRoute();
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
    header('Location: ' . $url, true, $statusCode);

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
    $prefix  = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 4)), 0, 4);
    $postfix = time();

    return $prefix . '-' . $postfix;
}

/**
 * Generate unique number
 *
 * @return string
 */
function generateUniqueNumber(): string
{
    return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 4)), 0, 2) . '-' . mt_rand(100000, 999999) . '-' . time();
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
    if (!defined('ROOLITH_ENV')) {
        return true;
    }

    return ROOLITH_ENV == 'development';
}

/**
 * Is production environment
 *
 * @return bool
 */
function isProductionEnvironment(): bool
{
    if (!defined('ROOLITH_ENV')) {
        return false;
    }

    return ROOLITH_ENV == 'production';
}

/**
 * Get user IP address
 *
 * @return mixed|string
 */
function getIpAddress(): mixed
{
    $ipAddress = 'UNKNOWN';

    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
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
        $findArray[] = '/{' . $key . '}/';
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
    return Config::get('version');
}

/**
 * Carbon diff for humans
 *
 * @param string $dateString
 * @return string
 */
function diffForHumans(string $dateString): string
{
    return Carbon::parse($dateString)->diffForHumans();
}

/**
 * Get module image URL
 *
 * @param string $imageName
 * @return string
 */
function getModuleImageUrl(string $imageName): string
{
    return url(APP_ADMIN_FILE_MANAGER_MODULE_DATA_DIR . $imageName);
}

/**
 * Whether data changed in an associative array
 *
 * @param array $updatedDataArray
 * @param array $originalDataArray
 * @return bool
 */
function isDataChanged(array $updatedDataArray, array $originalDataArray): bool
{
    $isChanged = false;

    foreach ($updatedDataArray as $key => $value) {
        if ($value != $originalDataArray[$key]) {
            $isChanged = true;
            break;
        }
    }

    return $isChanged;
}
