<?php

if (php_sapi_name() != "cli")
  return;

use Symfony\Component\Console\Application;

$application = new Application ("Cosy Application");
foreach (scandir (APP_PATH . '/commands/') as $filename)
{
  $info = pathinfo ($filename);
  if ($info ['extension'] != "php")
    continue;
  $class = '\\Cosy\\Command\\' . $info ['filename'];
  $application->add (new $class);
}
//$application->add (new \Cosy\Command\Email);
//$application->add (new \Cosy\Command\Task);

$application->run ();
