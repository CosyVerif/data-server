<?php

// Registering an autoloader
// WARNING: we cannot use Phalcon's autoloader,
// because it does not handle PHAR archives.
/*
$configuration = $di ['configuration'];
$loader = new \Phalcon\Loader ();
$loader->registerDirs ([
  APP_PATH . "/{$configuration->application->models_directory}",
  APP_PATH . "/{$configuration->application->views_directory}",
  APP_PATH . "/{$configuration->application->tasks_directory}",
]);
$loader->registerNamespaces ([
  '' => APP_PATH . "/{$configuration->application->models_directory}/",
  'Cosy' => APP_PATH . "/{$configuration->application->models_directory}/Cosy"
]);
$eventsManager = new \Phalcon\Events\Manager;
$eventsManager->attach('loader', function ($event, $loader) {
  if ($event->getType () == 'beforeCheckPath') {
    echo "--> " . $loader->getCheckedPath () . "\n";
  }
});

$loader->setEventsManager ($eventsManager);
$loader->register ();
unset ($configuration);
*/

// Use composer autoloader to load classes
require_once APP_PATH . '/../vendor/autoload.php';
