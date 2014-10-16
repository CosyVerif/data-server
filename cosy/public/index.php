<?php

use \Phalcon\Config\Adapter\Ini as Ini;

error_reporting (E_ALL);
define ('APP_PATH', realpath ('..'));

include APP_PATH . '/config/services.php';
include APP_PATH . '/config/loader.php';
include APP_PATH . '/app.php';

