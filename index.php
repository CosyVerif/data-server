<?php

require_once __DIR__ . '/cosy.php';

$conf = $di ['configuration'];

$session = new \Phalcon\Session\Bag ('cosy-core');
$cosy = \Cosy\Data\Cosy::instantiate (['resource' => 'http://cosy.io']);
$user = $cosy->createUser ([
  'username' => 'alban',
  'password' => 'toto',
  'email'    => 'alban.linard@lsv.ens-cachan.fr',
  'fullname' => 'Alban Linard',
]);
//var_dump ($user);

// TODO
// http://forum.phalconphp.com/discussion/3539/phalcon-websockets
