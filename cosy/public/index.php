<?php
error_reporting (E_ALL);

use \Phalcon\Config\Adapter\Ini as Ini;

$phar = Phar::running ();

if ($phar == "")
  define ('APP_PATH', realpath (__DIR__ . '/..'));
else
  define ('APP_PATH', "{$phar}/cosy");

include APP_PATH . '/config/services.php';
include APP_PATH . '/config/loader.php';
include APP_PATH . '/config/development.php';
include APP_PATH . '/app.php';
