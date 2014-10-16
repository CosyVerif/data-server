<?php

if (php_sapi_name() == "cli")
  $di = new \Phalcon\DI\FactoryDefault\CLI ();
else
  $di = new \Phalcon\DI\FactoryDefault ();

$di ['configuration'] = function () {
  $home = getenv ('HOME');
  $paths = [
    __DIR__ . "/config.ini",
    "/etc/cosy.ini",
    "{$home}/.cosy/cosy.ini",
    "cosy.ini"
  ];
  foreach ($paths as $path)
  {
    if (! is_file ($path))
      continue;
    $read = new \Phalcon\Config\Adapter\Ini ($path);
    if (isset ($config))
      $config->merge ($read);
    else
      $config = $read;
  }
  return $config;
};

$di ['view'] = function () use ($di) {
  $configuration = $di ['configuration'];
  $view = new View();
  $view->setViewsDir($configuration->application->views_directory);
  return $view;
};

$di ['url'] = function () use ($di) {
  $configuration = $di ['configuration'];
  $url = new UrlResolver();
  $url->setBaseUri($configuration->site->url);
  return $url;
};

$di ['mongo'] = function () use ($di) {
  $configuration = $di ['configuration'];
  $host = $configuration->mongodb->host;
  $port = $configuration->mongodb->port;
  $mongo = new MongoClient ("mongodb://{$host}:{$port}");
  return $mongo->selectDB ("cosy");
};

$di ['collectionManager'] = function () {
  // Used by MongoDB ODM
  return new \Phalcon\Mvc\Collection\Manager ();
};

$di ['session'] = function () {
  $session = new \Phalcon\Session\Adapter\Files ();
  $session->start ();
  return $session;
};

$di ['filter'] = function () {
  return new \Phalcon\Filter ();
};

$di ['security'] = function () {
  $security = new \Phalcon\Security ();
  $security->setWorkFactor(12);
  return $security;
};

$di ['validation'] = function () use ($di) {
  return new \Cosy\Validation ($di);
};

$di ['queue'] = function () use ($di) {
  $configuration = $di ['configuration'];
  return new \Phalcon\Queue\Beanstalk (array (
    'host' => $configuration->beanstalk->host,
    'port' => $configuration->beanstalk->port
  ));
};
