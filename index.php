<?php
use App\Core\System;

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
$app->bootstrap()
    ->processRequest()
    ->complete();
