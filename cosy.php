<?php

require_once __DIR__ . '/vendor/autoload.php';

//Create a DI
$di = new \Phalcon\DI\FactoryDefault ();
$di ['mongo'] = function () {
  $mongo = new Mongo ('mongodb://127.0.0.1:27017');
  return $mongo->selectDb ("cosy");
};
$di ['collectionManager'] = function () {
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
$di ['security'] = function(){
  $security = new \Phalcon\Security ();
  $security->setWorkFactor(12);
  return $security;
};
//$di ['email'] = function () {
//  return new \Cosy\Mail;
//};
$di ['configuration'] = function () {
  $home = getenv ('HOME');
  $paths = [
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
$di ['email'] = function () {
  return new \Cosy\Mail;
};
// TODO
// http://forum.phalconphp.com/discussion/3539/phalcon-websockets
