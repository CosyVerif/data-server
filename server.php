<?php

require 'vendor/autoload.php';

$app = new \Slim\Slim();
$app->add(new \CosyVerif\Server\Core());
$app->add(new \CosyVerif\Server\HttpBasicAuthentification());



$app->run();
