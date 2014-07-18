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
    Util::rrmdir($config["base_dir"]);
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

  public static function addModel($user_name, $model_name, $model_data)
  {
    $config = Util::getConfig();

    mkdir($config["base_dir"]."/users/".$user_name."/models/".$model_name);
    $info = array('name' => $model_name);
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/info.json", json_encode($info));
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/model.lua", $model_data);

    mkdir($config["base_dir"]."/users/".$user_name."/models/".$model_name."/editor");
    $info = array();
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/editor/info.json", json_encode($info));

    mkdir($config["base_dir"]."/users/".$user_name."/models/".$model_name."/patches");
    $info = array("patch_number" => 1);
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/patches/info.json", json_encode($info));

    $patch_data = "local p1 = function (model) model.x = 1 end";
    Util::addPatch($user_name, $model_name, $patch_data);
  }

  public static function addPatch($user_name, $model_name, $patch_data)
  {
    $config = Util::getConfig();
    $tmp = json_decode(file_get_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/patches/info.json"), TRUE);
    $patch_number = $tmp["patch_number"];
    $info = array('patch_number' => ($patch_number + 1));
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/patches/info.json", json_encode($info));
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/patches/".$patch_number.".lua", $patch_data);
  }

  public static function enterEditMode($user_name, $model_name, $token, $url)
  {
    $config = Util::getConfig();
    $info = array('token' => $token, 'url' => $url);
    file_put_contents($config["base_dir"]."/users/".$user_name."/models/".$model_name."/editor/info.json", json_encode($info));
  }

  public static function rrmdir($path)
  {
    foreach(glob($path . '/*') as $file) 
    {
      if(is_dir($file))
      {
        Util::rrmdir($file);
        rmdir($file);
      }
      else
        unlink($file);      
    }
  }
}