<?php
use App\Core\System;

define('APP_ROOT', __DIR__);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
$app->bootstrap()
    ->processRequest()
    ->complete();

$data = [
    ['name' => 'z', 'age' => 10],
    ['name' => 'y', 'age' => 5],
    ['name' => 'x', 'age' => 54],
];
$result = \App\Core\Utils\_::orderByString($data, 'name');
p($result);
