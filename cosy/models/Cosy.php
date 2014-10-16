<?php

final class Cosy extends \Cosy\Base
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
    return \Cosy\User::instantiate ($this, $parameters);
  }
}
