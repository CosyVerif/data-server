<?php

namespace Cosy\Data;

final class Cosy extends Base
{
  public $is_public   = true;
  public $check_email = true;

  protected function validation ()
  {
  }

  public static function instantiate ($parameters)
  {
    $result = new Cosy;
    $result->resource = $parameters ['resource'];
    $result->save ();
    return $result;
  }

  public function createUser ($parameters)
  {
    assert (is_array ($parameters));
    return User::instantiate ($this, $parameters);
  }
}
