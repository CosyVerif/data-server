<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class StreamModel extends StreamBase
{
  public static function read($url)
  {
    global $app;
    //Verify existing resource
    if (!file_exists($app->config["base_dir"].$url))
    {
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    else if(!file_exists($app->config["base_dir"].$url."/info.json"))
    {
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    $lua = file_get_contents($app->config["base_dir"].$url."/model.lua");
    if ($lua == FALSE || $info == FALSE)
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      return null;
    } 
    $data = array ('name' => $info["name"], 'data' => $lua);
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $data;
  }

  public static function write($url, $data)
  {
    global $app;
    //Write ressource
    $is_ok = false;
    $info = "";
    $auth = "";
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$url) ||   
        StreamBase::isEmptyDir($app->config["base_dir"].$url))
    {
      if(!file_exists($app->config["base_dir"].$url))
      {
        mkdir($app->config["base_dir"].$url);
      }
      $tmp = array();
      mkdir($app->config["base_dir"].$url."/patches");
      $tmp["name"] = "Patch list";
      file_put_contents($app->config["base_dir"].$url."/patches/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$url."/editor");
      $tmp["name"] = "Editor information file";
      $tmp["token"] = "";
      $tmp["url"] = "";
      file_put_contents($app->config["base_dir"].$url."/editor/info.json", json_encode($tmp));
      $app->response->setStatus(STATUS_CREATED);
    } 
    else 
      $app->response->setStatus(STATUS_OK);
    if (array_key_exists("name", $data))
      file_put_contents($app->config["base_dir"].$url."/info.json", json_encode(array('name' => $data["name"])));
    if (array_key_exists("data", $data))
      file_put_contents($app->config["base_dir"].$url."/model.lua", $data["data"]);
    return $is_ok = true; 
  }

  public static function writePatch($url, $data)
  {
    global $app;
    $is_ok = false;
    $info = "";
    $auth = "";
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$url) ||   
        StreamBase::isEmptyDir($app->config["base_dir"].$url))
    {
      if(!file_exists($app->config["base_dir"].$url))
      {
        mkdir($app->config["base_dir"].$url);
      }
      $tmp = array();
      mkdir($app->config["base_dir"].$url."/patches");
      $tmp["name"] = "Patch list";
      file_put_contents($app->config["base_dir"].$url."/patches/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$url."/editor");
      $tmp["name"] = "Editor information file";
      file_put_contents($app->config["base_dir"].$url."/editor/info.json", json_encode($tmp));
      $app->response->setStatus(STATUS_CREATED);
    } 
    else 
      $app->response->setStatus(STATUS_OK);
    $pathInfo = pathinfo($app->config["base_dir"].$url);
    $info = array('name' => $pathInfo['basename']);
    file_put_contents($app->config["base_dir"].$url."/info.json", json_encode($info));
    file_put_contents($app->config["base_dir"].$url."/model.lua", $data);
    return $is_ok = true; 
  }

  public static function generateToken()
  {
    return uniqid("cosyVerif", true);
  }

  public static function saveToken($url, $data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    $info["token"] = $data;
    file_put_contents($app->config["base_dir"].$url."/info.json", json_encode($info));
    return true;
  }

  public static function getToken($url)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    return $info["token"];
  }

  public static function saveUrl($url, $data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    $info["url"] = $data;
    file_put_contents($app->config["base_dir"].$url."/info.json", json_encode($info));
    return true;
  }

  public static function getUrl($url)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    return $info["url"];
  }

  public static function formToken($url, $token_user)
  {

  }
}
?>