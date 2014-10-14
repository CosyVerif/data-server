<?php

require_once __DIR__ . '/vendor/autoload.php';

try
{


  //Create a DI
  $di = new Phalcon\DI\FactoryDefault ();
  $di ['mongo'] = function () {
    $mongo = new Mongo ('mongodb://127.0.0.1:27017');
    return $mongo->selectDb ("cosy");
  };
  $di ['collectionManager'] = function () {
      return new Phalcon\Mvc\Collection\Manager ();
  };
  $di ['session'] = function () {
    $session = new Phalcon\Session\Adapter\Files ();
    $session->start ();
    return $session;
  };
  $di ['filter'] = function () {
    return new \Phalcon\Filter ();
  };
  $di ['security'] = function(){
    $security = new Phalcon\Security ();
    $security->setWorkFactor(12);
    return $security;
  };
  $di ['email'] = function () {
    return new Cosy\Mail;
  };
  $di ['configuration'] = function () {
    $paths = [
      '/etc/cosy.ini',
      '~/.cosy/cosy.ini',
      'cosy.ini'
    ];
    $global = new \Phalcon\Config\Adapter\Ini ('cosy.ini');
    $local  = 
  };

  $filter = $di ['filter'];
  $filter->add ('url', function ($value) {
    return $value; // FIXME
  });

  $session = new Phalcon\Session\Bag ('cosy-core');
  $cosy = \Cosy\Data\Cosy::instantiate (['resource' => 'http://cosy.io']);
  $user = $cosy->createUser ('alinard', 'mypass');
  var_dump ($user);
}
catch (\Phalcon\Exception $e)
{
  echo "PhalconException: ", $e->getMessage();
}

// TODO
// http://forum.phalconphp.com/discussion/3539/phalcon-websockets
