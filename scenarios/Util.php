<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

class Util extends PHPUnit_Framework_TestCase
{
  public static function addUserRoot(){
    Util::rrmdir("resources", "resources");
    $info = array('name' => 'Root base');
    $auth = array('login' => 'root',
                  'password' => password_hash('roottoto', PASSWORD_DEFAULT));
    file_put_contents("resources/info.json", json_encode($info));
    mkdir("resources/users");
    $info['name'] = "User list";
    file_put_contents("resources/users/info.json", json_encode($info));
    mkdir("resources/projects");
    $info['name'] = "Project list";
    file_put_contents("resources/projects/info.json", json_encode($info));
    mkdir("resources/users/root");
    $info['name'] = "Root base";
    file_put_contents("resources/users/root/info.json", json_encode($info));
    file_put_contents("resources/users/root/auth.json", json_encode($auth));
  }

  public static function addUser($first_name, $last_name, $login, $password, $u_c, $u_m, $u_d, $is_public){
    $info = array('first_name' => $first_name,
                  'last_name' => $last_name,
                  'name' => $first_name.' '.$last_name);
    $auth = array('login' => $login,
                  'password' => password_hash($login.$password, PASSWORD_DEFAULT),
                  'permissions' => array("user_create" => $u_c, "user_modify" => $u_m, "user_delete" => $u_d),
                  'can_public' => $is_public);
    mkdir("resources/users/".$login);
    file_put_contents("resources/users/".$login."/info.json", json_encode($info));
    file_put_contents("resources/users/".$login."/auth.json", json_encode($auth));
    $info = array();
    mkdir("resources/users/".$login."/formalisms");
    $info["name"] = "Formalism list";
    file_put_contents("resources/users/".$login."/formalisms/info.json", json_encode($info));
    mkdir("resources/users/".$login."/models");
    $info["name"] = "Model list";
    file_put_contents("resources/users/".$login."/models/info.json", json_encode($info));
    mkdir("resources/users/".$login."/scenarios");
    $info["name"] = "Scenario list";
    file_put_contents("resources/users/".$login."/scenarios/info.json", json_encode($info));
    mkdir("resources/users/".$login."/services");
    $info["name"] = "Service list";
    file_put_contents("resources/users/".$login."/services/info.json", json_encode($info));
    mkdir("resources/users/".$login."/executions");
    $info["name"] = "Execution list";
    file_put_contents("resources/users/".$login."/executions/info.json", json_encode($info));
    mkdir("resources/users/".$login."/projects");
    $info["name"] = "Project list";
    file_put_contents("resources/users/".$login."/projects/info.json", json_encode($info));
  }

  public static function rrmdir($path, $newPath){
    foreach(glob($newPath . '/*') as $file) {
        if(is_dir($file))
            Util::rrmdir($path, $file);
        else
          unlink($file);      
    }
    if($newPath != $path)
      rmdir($newPath);
  }
}