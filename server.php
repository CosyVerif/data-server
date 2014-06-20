<?php

require 'vendor/autoload.php';

$server_config_file = json_decode(file_get_contents('box.json'), TRUE);
$user_config_file = parse_ini_file("config.ini");
$user_config_file = array_map('strtolower', $user_config_file);
$config = array_merge($server_config_file, $user_config_file);

$app = new \Slim\Slim();
$app->config = $config;
$app->add(new \CosyVerif\Server\Core());
$app->add(new \CosyVerif\Server\Permission());
$app->add(new \CosyVerif\Server\HttpBasicAuthentification());

$app->run();
