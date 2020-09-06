<?php
use App\Core\System;

define('APP_ROOT', __DIR__);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
$app->bootstrap()
    ->processRequest()
    ->complete();

$array = [
    ['developer' => ['id' => 1, 'name' => 'Taylor']],
    ['developer' => ['id' => 2, 'name' => 'Abigail']],
];
$result = \App\Utils\_::pluck($array, 'developer.name');
p($result);
