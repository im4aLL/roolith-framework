<?php
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