<?php
namespace Cosy\Data;

final class User extends Base
{
  private $resource;
  private $username;

  public $firstname;
  public $lastname;
  public $email;
  public $password;

  public $is_admin  = false;
  public $is_active = false;
  public $is_public = true;

  private $validation_key;
  private $share_read;
  private $share_write;

  protected function validation ()
  {
    // Check that current user has permission:
    $session = $this->getDI () ['cosy-core'];
    return $session->has ('username')
        && $session->get ('username') == $this->username
         ;
  }

  public static function instantiate ($parameters)
  {
    // Create instance:
    $result = new User;
    // Get required components:
    $filter   = $result->getDI () ['filter'];
    $security = $result->getDI () ['security'];
    $email    = $result->getDI () ['email'];
    // Extract and sanitize parameters:
    $result->resource = $filter->sanitize ($parameters ['resource'], 'url');
    $result->username = $filter->sanitize ($parameters ['username'], 'alphanum');
    $result->password = $security->hash ($parameters ['password']);
    $result->validation_key = uniqid ();
    return $result;
  }

}
