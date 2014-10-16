<?php

$configuration = $di ['configuration'];
// Registering an autoloader
$loader = new \Phalcon\Loader ();
$loader->registerDirs ([
  $configuration->application->models_directory,
  $configuration->application->views_directory,
  $configuration->application->tasks_directory,
])->register();
$loader->registerNamespaces ([
  'Cosy' => "{$configuration->application->models_directory}/Cosy/"
]);
unset ($configuration);

// Use composer autoloader to load vendor classes
require_once __DIR__ . '/../../vendor/autoload.php';
