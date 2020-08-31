<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constant.php';

//$app = new \App\Core\System();
//$app->connectToDatabase();
//
//$db = \App\Core\DatabaseFactory::getInstance();
//$users = $db->query("SELECT * FROM users")->get();
//print_r($users);
//
//$app->disconnectFromDatabase();

$db = new \Roolith\Database();
$db->connect(\Roolith\Config::get('database'));
$users = $db->query("SELECT * FROM users")->get();
print_r($users);
$db->disconnect();