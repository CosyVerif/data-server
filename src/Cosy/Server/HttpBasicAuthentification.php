<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class HttpBasicAuthentification extends \Slim\Middleware
{
  protected $realm = "CosyVerif";

  public static function register()
  {
    global $app;
    $app->add(new HttpBasicAuthentification());
  }

  public function call()
  {
    global $app;
    $req = $this->app->request();
    $res = $this->app->response();
    $authUser = $req->headers('PHP_AUTH_USER');
    $authPass = $req->headers('PHP_AUTH_PW');
    if (!isset($authUser) && !isset($authPass))
    {
      $app->user = null;
      $this->next->call();
    } 
    else if (isset($authUser) && isset($authPass) && $this->authenticate($authUser, $authPass))
    {
      $this->next->call();
    } 
    else 
    {
      $res->status(STATUS_UNAUTHORIZED);
      $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
    }
  }

  public function authenticate($user, $password)
  {
    global $app;
    if (!file_exists($app->config("base_dir")."/users/".$user))
    {
      return false;
    } 
    $auth = json_decode(file_get_contents($app->config("base_dir")."/users/".$user."/auth.json"), TRUE);
    $password = $user.$password;
    if ($auth["login"] == $user && password_verify($password, $auth["password"]) && $auth["activate"] == true)
    {
      $app->user = $auth;
      return true;
    } 
    else
      return false;
  } 
}
