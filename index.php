<?php
use App\Core\System;

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constant.php';

$app = new System();
$app->bootstrap();


$app->complete();