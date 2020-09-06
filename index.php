<?php
use App\Core\System;

define('APP_ROOT', __DIR__);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
$app->bootstrap()
    ->processRequest()
    ->complete();

$array = ['products' => ['desk' => ['price' => 100]]];
$result = \App\Utils\_::set($array, 'products.desk.price', 200);
p($result);
