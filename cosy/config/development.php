<?php

$configuration = $di ['configuration'];
// http://stackoverflow.com/questions/19821082/collate-several-xdebug-coverage-results-into-one-report
if ($configuration->development->coverage)
{
  $coverage = new PHP_CodeCoverage;
  $coverage->start('Cosy coverage');
  function shutdown ()
  {
    global $coverage;
    $coverage->stop();
    $cov = serialize ($coverage);
    $directory = getcwd () . "/coverage";
    if (! is_dir ($directory))
      mkdir ($directory);
    file_put_contents($directory . '/data.' . date('U') . '.cov', $cov);
  }
  register_shutdown_function ('shutdown');
}
unset ($configuration);
