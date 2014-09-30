<?php

require 'vendor/autoload.php';

$app = new \Slim\Slim();
$app->base_directory = getcwd();

$default_config = parse_ini_file(__DIR__              . "/config/server-config.ini");
$user_config    = parse_ini_file($app->base_directory . "/config/server-config.ini");
$user_config    = array_map('strtolower', $user_config);
$config = array_merge($default_config, $user_config);

//$config["activate_message"] = file_get_contents("config/activate-message.txt");

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

if (substr($config['base_dir'], 0, 1) != '/') {
  $config['base_dir'] = $app->base_directory . '/' . $app->config["base_dir"];
}
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

$app->run();
