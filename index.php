<?php
use App\Core\System;

const APP_ROOT = __DIR__;

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
try {
    $app->bootstrap()
        ->processRequest()
        ->complete();
} catch (\App\Core\Exceptions\Exception|\Roolith\Configuration\Exception\InvalidArgumentException $e) {
    print $e->getMessage();
}