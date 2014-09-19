<?php

require 'vendor/autoload.php';

$server_config_file = json_decode(file_get_contents('box.json'), TRUE);
$user_config_file = parse_ini_file("config.ini");
$user_config_file = array_map('strtolower', $user_config_file);
$config = array_merge($server_config_file, $user_config_file);

$app = new \Slim\Slim();
$app->config = $config;

if (array_key_exists("coverage", $config) && $config["coverage"]) {
  $coverage = new PHP_CodeCoverage;
  $coverage->start('server');
  $app->get('/STOP', function () use ($app, $coverage) {
    $coverage->stop();
    $writer = new PHP_CodeCoverage_Report_HTML;
    $writer->process($coverage, 'coverage');
  });
}

\CosyVerif\Server\Routing\WebSiteMiddleware::register();
\CosyVerif\Server\Routing\UserMiddleware::register();
\CosyVerif\Server\Routing\ProjectMiddleware::register();
\CosyVerif\Server\Routing\ModelMiddleware::register();
\CosyVerif\Server\HttpBasicAuthentification::register();
\CosyVerif\Server\Constants::register();
//$app->add(new \CosyVerif\Server\Core());
//$app->add(new \CosyVerif\Server\Permission());


$app->run();
