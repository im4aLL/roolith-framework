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