<?php

namespace Cosy\Data;

final class Cosy extends Base
{
  public $resource;
  public $is_public   = true;
  public $check_email = true;

  protected function validation ()
  {
    return filter_var ($this->resource    , FILTER_VALIDATE_URL)
        && filter_var ($this->is_public   , FILTER_VALIDATE_BOOLEAN)
        && filter_var ($this->check_email , FILTER_VALIDATE_BOOLEAN)
         ;
  }

  public static function instantiate ($parameters)
  {
    $result = new Cosy;
    $filter = $result->getDI () ['filter'];
    $result->resource = $filter->sanitize ($parameters ['resource'], 'url');
    return $result;
  }

  public function createUser ($username, $password)
  {
    $result   = User::instantiate ([
      'resource' => "{$this->resource}/users/{$username}",
      'username' => $username,
      'password' => $password
    ]);
    return $result;
  }
}
