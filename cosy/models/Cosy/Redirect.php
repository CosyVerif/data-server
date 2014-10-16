<?php

namespace Cosy;

final class Redirect extends Base
{
  private $target;

  protected function validation ()
  {
  }

  public static function instantiate ($parent, $parameters)
  {
    // Create instance:
    $result = new Redirect;
    // Get required components:
    $filter = $result->getDI () ['filter'];
    // Extract and sanitize parameters:
    $result->resource = $filter->sanitize ($parameters ['resource'], 'url');
    $result->target   = $filter->sanitize ($parameters ['target'  ], 'url');
    // Save resource:
    $result->save ();
    return $result;
  }
}
