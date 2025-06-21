<?php
use Carbon\Carbon;
use Roolith\Configuration\Config;

/**
 * Print anything
 *
 * @param $any
 * @param bool $exit
 */
function p($any, $exit = false) {
    echo '<pre>';
    print_r($any);
    echo '</pre>';

    if ($exit) {
        die();
    }
}

/**
 * Prefix app url in path
 *
 * @param $path
 * @return string
 */
function url($path) {
    try {
        return \Roolith\Configuration\Config::get('baseUrl') . $path;
    } catch (\Roolith\Configuration\Exception\InvalidArgumentException $e) {
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
function route($name, $settings = []) {
    $routerInstance = \App\Core\RouterFactory::getInstance();

    return $routerInstance->getUrlByName($name, $settings);
}

/**
 * Get active route
 *
 * @return array
 */
function getActiveRoute() {
    $routerInstance = \App\Core\RouterFactory::getInstance();

    return $routerInstance->activeRoute();
}

/**
 * Get message
 *
 * @param $name
 * @return mixed|null
 */
function __($name) {
    return \App\Utils\Str::getMessage($name);
}

/**
 * Redirect to URL
 *
 * @param string $url
 * @param integer $statusCode
 * @return void
 */
function redirect($url, $statusCode = 303) {
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
function redirectToRoute($routeName, $settings = [], $statusCode = 303) {
    $url = route($routeName, $settings);

    redirect($url, $statusCode);
}

/**
 * Generate unique alpha numeric number
 *
 * @return string
 */
function generateUniqueAlphaNumericNumber() {
    $prefix  = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ',4)),0,4);
    $postfix = time();

    return $prefix.'-'.$postfix;
}

/**
 * Generate unique number
 *
 * @return string
 */
function generateUniqueNumber() {
    return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ',4)),0,2).'-'.mt_rand(100000,999999).time();
}

/**
 * Get Current date and time
 *
 * @return string
 */
function getCurrentDateTime() {
    return Carbon::now()->toDateTimeString();
}

/**
 * Get today's date
 *
 * @return string
 */
function getCurrentDate() {
    return Carbon::now()->toDateString();
}

/**
 * Is is dev environment
 *
 * @return bool
 */
function isDevEnvironment() {
    return ROOLITH_ENV == 'development';
}

/**
 * Get user IP address
 *
 * @return mixed|string
 */
function getIpAddress() {
    $ipAddress = 'UNKNOWN';

    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if(isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if(isset($_SERVER['HTTP_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } else if(isset($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    }

    return $ipAddress;
}

/**
 * If string contain a piece
 *
 * @param $string
 * @param $piece
 * @return bool
 */
function stringContains($string, $piece) {
    return strpos($string, $piece) !== false;
}

/**
 * Parse basic template
 *
 * @param $string
 * @param array $data
 * @return string|string[]|null
 */
function parseBasicTemplate($string, $data = []) {
    $findArray = [];
    $replaceArray = [];

    foreach ($data as $key => $value) {
        $findArray[] = '/{'.$key.'}/';
        $replaceArray[] = $value;
    }

    return preg_replace($findArray, $replaceArray, $string);
}

/**
 * Get version
 * 
 * @return string
 */
function getVersion() {
    return Config::get('version');
}