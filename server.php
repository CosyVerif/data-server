<?php

require 'vendor/autoload.php';

$server_config_file = json_decode(file_get_contents('box.json'), TRUE);
$user_config_file = parse_ini_file("config/server-config.ini");
$user_config_file = array_map('strtolower', $user_config_file);
$config = array_merge($server_config_file, $user_config_file);

$config["activate_message"] = file_get_contents("config/message-publish.txt");

if (array_key_exists("coverage", $config) && $config["coverage"]) {
  // http://stackoverflow.com/questions/19821082/collate-several-xdebug-coverage-results-into-one-report
  if (!is_dir("coverage")) {
    mkdir("coverage");
  }
  $coverage = new PHP_CodeCoverage;
  $coverage->start('Site coverage');
  function shutdown()
  {
    global $coverage;
    $coverage->stop();
    $cov = serialize($coverage); //serialize object to disk
    file_put_contents('coverage/data.' . date('U') . '.cov', $cov);
  }
  register_shutdown_function('shutdown');
}

$app = new \Slim\Slim();
$app->config = $config;
\CosyVerif\Server\Routing\Routing::register();
\CosyVerif\Server\HttpBasicAuthentification::register();
\CosyVerif\Server\Constants::register();

$app->get("/", function () use ($app) {
  echo "Welcome to CosyVerif";
});

$app->get('/initializes(/)', function() use($app)
{
  \CosyVerif\Server\Routing\BaseResource::newResource("")->initializes_server();
});

\CosyVerif\Server\CrossOrigin::register();
\CosyVerif\Server\Routing\UserMiddleware::register();
\CosyVerif\Server\Routing\ProjectMiddleware::register();
\CosyVerif\Server\Routing\ModelMiddleware::register();
\CosyVerif\Server\HttpBasicAuthentification::register();
\CosyVerif\Server\Constants::register();

$app->run();
