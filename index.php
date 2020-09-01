<?php
use App\Core\System;

define('APP_ROOT', __DIR__);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();

try {
    $app->bootstrap();
} catch (\App\Core\Exceptions\Exception $e) {
    echo $e->getMessage();
}

$app->processRequest()->complete();
