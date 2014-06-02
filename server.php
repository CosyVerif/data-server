<?php

require 'vendor/autoload.php';

$ini = json_decode(file_get_contents('box.json'), TRUE);

$app = new \Slim\Slim();
$app->server = $ini;
$app->add(new \CosyVerif\Server\Core());
$app->add(new \CosyVerif\Server\Permission());
$app->add(new \CosyVerif\Server\HttpBasicAuthentification());

$app->run();
