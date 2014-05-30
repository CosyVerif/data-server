<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class HttpBasicAuthentification extends \Slim\Middleware
{
  protected $realm = "CosyVerif";

  public function call()
  {
    global $app;
    $req = $this->app->request();
    $res = $this->app->response();
    $authUser = $req->headers('PHP_AUTH_USER');
    $authPass = $req->headers('PHP_AUTH_PW');
    if (!isset($authUser) && !isset($authPass)){
      $app->user = array('user_type' => USER_DEFAULT, 'login' => 'default');
      $this->next->call();
    } else if (isset($authUser) && isset($authPass) && $this->authentificate($authUser, $authPass)){
      $this->next->call();
    } else {
      $res->status(STATUS_UNAUTHORIZED);
      $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
    }
  }

  public function authentificate($user, $password)
  {
    global $app;
    if (!file_exists("resources/users/".$user)){
      return false;
    }
    $auth = json_decode(file_get_contents("resources/users/".$user."/auth.json"), TRUE);
    $password = $user.$password;
    if ($auth["login"] == $user && password_verify($password, $auth["password"])){
      $app->user = $auth;
      return true;
    } else
      return false;
  } 
}
?>