<?php
require_once 'Constants.php';

class Util
{
  public static function getConfig()
  {
    $server_config_file = json_decode(file_get_contents('box.json'), TRUE);
    $user_config_file = parse_ini_file("config.ini");
    $user_config_file = array_map('strtolower', $user_config_file);
    $config = array_merge($server_config_file, $user_config_file);
    return $config;
  }

  public static function addUserRoot()
  {
    $config = Util::getConfig();
    Util::rrmdir($config["base_dir"], $config["base_dir"]);
    $info = array('name' => 'Root base');
    $auth = array('login' => $config["user_root"],
                  'password' => password_hash($config["user_root"].'toto', PASSWORD_DEFAULT));
    file_put_contents($config["base_dir"]."/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users");
    $info['name'] = "User list";
    file_put_contents($config["base_dir"]."/users/info.json", json_encode($info));
    mkdir($config["base_dir"]."/projects");
    $info['name'] = "Project list";
    file_put_contents($config["base_dir"]."/projects/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/root");
    $info['name'] = "Root base";
    file_put_contents($config["base_dir"]."/users/root/info.json", json_encode($info));
    file_put_contents($config["base_dir"]."/users/root/auth.json", json_encode($auth));
  }

  public static function addUser($first_name, $last_name, $login, $password, $admin_user, $can_public)
  {
    $config = Util::getConfig();
    $info = array('first_name' => $first_name,
                  'last_name' => $last_name,
                  'name' => $first_name.' '.$last_name);
    $auth = array('login' => $login,
                  'password' => password_hash($login.$password, PASSWORD_DEFAULT),
                  'admin_user' => $admin_user,
                  'can_public' => $can_public);
    mkdir($config["base_dir"]."/users/".$login);
    file_put_contents($config["base_dir"]."/users/".$login."/info.json", json_encode($info));
    file_put_contents($config["base_dir"]."/users/".$login."/auth.json", json_encode($auth));
    $info = array();
    mkdir($config["base_dir"]."/users/".$login."/formalisms");
    $info["name"] = "Formalism list";
    file_put_contents($config["base_dir"]."/users/".$login."/formalisms/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/".$login."/models");
    $info["name"] = "Model list";
    file_put_contents($config["base_dir"]."/users/".$login."/models/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/".$login."/scenarios");
    $info["name"] = "Scenario list";
    file_put_contents($config["base_dir"]."/users/".$login."/scenarios/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/".$login."/services");
    $info["name"] = "Service list";
    file_put_contents($config["base_dir"]."/users/".$login."/services/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/".$login."/executions");
    $info["name"] = "Execution list";
    file_put_contents($config["base_dir"]."/users/".$login."/executions/info.json", json_encode($info));
    mkdir($config["base_dir"]."/users/".$login."/projects");
    $info["name"] = "Project list";
    file_put_contents($config["base_dir"]."/users/".$login."/projects/info.json", json_encode($info));
  }

  public static function rrmdir($path, $newPath)
  {
    foreach(glob($newPath . '/*') as $file) 
    {
      if(is_dir($file))
        Util::rrmdir($path, $file);
      else
        unlink($file);      
    }
    if($newPath != $path)
      rmdir($newPath);
  }
}