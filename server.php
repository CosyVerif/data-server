<?php

require 'vendor/autoload.php';

$app = new \Slim\Slim();

new \CosyVerif\Server\Core($app);

$app->run();
