<?php

require 'vendor/autoload.php';

\CosyVerif\Server\Constants::register();

$base_directory = getcwd();

$default_config = parse_ini_file(__DIR__         . "/config/server-config.ini");
$user_config    = parse_ini_file($base_directory . "/config/server-config.ini");
$user_config    = array_map('strtolower', $user_config);
$config         = array_merge($default_config, $user_config);

if (substr($config['base_dir'], 0, 1) != '/') {
  $config['base_dir'] = $base_directory . '/' . $config["base_dir"];
}
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
foreach ($config as $k => $v)
{
  $app->config($k, $v);
}


\CosyVerif\Server\Routing::register ();
\CosyVerif\Server\HttpBasicAuthentification::register();
\CosyVerif\Server\CrossOrigin::register();

$app->get("/", function () use ($app) {
  echo "Welcome to CosyVerif";
});

$app->options("/:x+", function () use ($app) {
  global $app;
  $request  = $app->request ();
  $response = $app->response();
  $accept_headers = $request->headers->get('Access-Control-Request-Headers');
  $accept_methods = $request->headers->get('Access-Control-Request-Method');
  $accept_origin  = $request->headers->get('Origin');
  $response->header('Accept', 'HEAD,GET,PUT,POST,PATCH,DELETE,OPTIONS');
  $response->header('Access-Control-Allow-Headers', $accept_headers);
  $response->header('Access-Control-Allow-Methods', $accept_methods);
  $response->header('Access-Control-Allow-Origin' , $accept_origin);
});

$app->run();
