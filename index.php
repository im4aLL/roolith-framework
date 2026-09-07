<?php

use App\Core\ErrorHandler;
use App\Core\System;

const APP_ROOT = __DIR__;
date_default_timezone_set('America/Edmonton');

session_start();

require_once __DIR__ . '/vendor/autoload.php';

try {
    $app = new System();
    $app->bootstrap()
        ->processRequest()
        ->complete();
} catch (\Throwable $e) {
    ErrorHandler::handle($app ?? null, $e);
}
