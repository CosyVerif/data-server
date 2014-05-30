<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

class Util extends PHPUnit_Framework_TestCase
{
  public static function addUserRoot($is_public){
    Util::rrmdir("resources/users");
    $info = array('first_name' =>'Default',
                  'last_name' => 'Default');
    $auth = array('login' => 'root',
                  'password' => password_hash('roottoto', PASSWORD_DEFAULT),
                  'user_type' => USER_ADMIN,
                  'is_public' => $is_public);
    mkdir("resources/users/root");
    file_put_contents("resources/users/root/info.json", json_encode($info));
    file_put_contents("resources/users/root/auth.json", json_encode($auth));
  }

  public static function addUser($first_name, $last_name, $login, $password, $user_type, $is_public){
    $info = array('first_name' => $first_name,
                  'last_name' => $last_name);
    $auth = array('login' => $login,
                  'password' => password_hash($login.$password, PASSWORD_DEFAULT),
                  'user_type' => $user_type,
                  'is_public' => $is_public);
    mkdir("resources/users/".$login);
    file_put_contents("resources/users/".$login."/info.json", json_encode($info));
    file_put_contents("resources/users/".$login."/auth.json", json_encode($auth));
  }

  public static function rrmdir($dir){
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            Util::rrmdir($file);
        else
            unlink($file);
    }
    if($dir != "resources/users")
      rmdir($dir);
  }
}