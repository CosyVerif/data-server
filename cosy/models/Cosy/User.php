<?php
namespace Cosy;

final class User extends Base
{
  public $parent;
  public $username;
  public $password;
  public $validation_key;

  public $fullname;
  public $email;

  public $is_active = false;
  public $is_public = true;

  public $share_read = true;
  public $share_write = true;

  protected function validation ()
  {
    // Check that current user has write permission:
//    $session = $this->getDI () ['cosy-core'];
//    return $session->has ('username')
//        && $session->get ('username') == $this->username
//         ;
  }

  protected function afterCreate ()
  {
    $queue = $this->getDI () ['queue'];
    $queue->put (['user-validation' => $this->resource]);
  }

  protected function afterUpdate ()
  {
  }

  public static function instantiate ($parent, $parameters)
  {
    // Get required components:
    $security   = $parent->getDI () ['security'];
    // Build object:
    $result                 = new User;
    $result->parent         = $parent->getResource ();
    $result->username       = $parameters ['username'];
    $result->resource       = "{$result->parent}/users/{$result->username}";
    $result->password       = $security->hash ($parameters ['password']);
    $result->email          = $parameters ['email'];
    $result->fullname       = $parameters ['fullname'];
    $result->validation_key = $result->username . '/' . uniqid ();
    $result->save ();
    return $result;
  }

  public function enable ($key)
  {
    assert ($key == $this->validation_key);
    $this->is_active      = true;
    $this->validation_key = null;
  }

  public function rename ($username)
  {
    $resource = $this->resource;
    assert ($username != $this->username);
    $result->username  = $filter->sanitize ($username, 'identifier');
    $result->resource  = $filter->sanitize ("{$result->parent}/users/{$result->username}", 'url');
    Redirect::instantiate ($this->parent, [
      'resource' => $resource,
      'target'   => $result->resource
    ]);
  }

  public function update ($parameters)
  {

  }


}
