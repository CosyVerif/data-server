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
    $resource = file_get_contents($app->config["base_dir"].$url."/model.lua");
    if ($resource == FALSE)
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      return null;
    }    
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','cosy/model');
    return $resource;
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

}
?>