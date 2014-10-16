<?php

if (php_sapi_name() != "cli")
  return;

use \Phalcon\CLI\Console as ConsoleApp;

define  ('VERSION', '1.0.0');
defined ('APPLICATION_PATH')
 || define ('APPLICATION_PATH', realpath (dirname(__FILE__)));

$console = new ConsoleApp;
$console->setDI ($di);

$arguments = array();
foreach($argv as $k => $arg) {
  if ($k == 1) {
     $arguments ['task'] = $arg;
  } elseif ($k == 2) {
     $arguments ['action'] = $arg;
  } elseif ($k >= 3) {
    $arguments ['params'] [] = $arg;
  }
}

define ('CURRENT_TASK'  , (isset ($argv [1]) ? $argv [1] : null));
define ('CURRENT_ACTION', (isset ($argv [2]) ? $argv [2] : null));

try {
  $console->handle ($arguments);
}
catch (\Phalcon\Exception $e) {
  echo $e->getMessage();
  exit(255);
}
