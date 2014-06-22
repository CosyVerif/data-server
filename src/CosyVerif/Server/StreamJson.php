<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class StreamJson
{
  public static function readResource($url)
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
    $resource = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    if (!is_array($resource))
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      return null;
    }    
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $resource;
  }

  public static function readResourceList($url)
  {
    global $app;
    //Verify existing resource list
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
    $data = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config["base_dir"].$url.'/*') as $file) 
    {
      if (!is_dir($file))
        continue;
      else if (file_exists($file."/info.json"))
      { 
        $tmp = json_decode(file_get_contents($file."/info.json"), TRUE);
        $resourceList[] = array('href' => $file, 'name' => $tmp["name"]);
      }
    }
    $data["resource_list"] = $resourceList;
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
    if (file_exists($app->config["base_dir"].$url."/info.json"))
    {
      // Resource not found
      $info = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
      //Update resource
      foreach ($data["info"] as $field => $value)
      {
        $info[$field] = $value;
      }
      $auth = json_decode(file_get_contents($app->config["base_dir"].$url."/auth.json"), TRUE);
      //Update resource
      foreach ($data["auth"] as $field => $value)
      {
        $info[$field] = $value;
      }
      $app->response->setStatus(STATUS_OK);
    } 
    else 
    {
      if(!file_exists($app->config["base_dir"].$url))
      {
        mkdir($app->config["base_dir"].$url);
        $routeName = $app->router()->getCurrentRoute()->getName();
        if ($routeName == "user" || $routeName == "project")
        {
          $tmp = array();
          mkdir($app->config["base_dir"].$url."/formalisms");
          $tmp["name"] = "Formalism list";
          file_put_contents($app->config["base_dir"].$url."/formalisms/info.json", json_encode($tmp));
          mkdir($app->config["base_dir"].$url."/models");
          $tmp["name"] = "Model list";
          file_put_contents($app->config["base_dir"].$url."/models/info.json", json_encode($tmp));
          mkdir($app->config["base_dir"].$url."/scenarios");
          $tmp["name"] = "scenarios list";
          file_put_contents($app->config["base_dir"].$url."/scenarios/info.json", json_encode($tmp));
          mkdir($app->config["base_dir"].$url."/services");
          $tmp["name"] = "Service list";
          file_put_contents($app->config["base_dir"].$url."/services/info.json", json_encode($tmp));
          mkdir($app->config["base_dir"].$url."/executions");
          $tmp["name"] = "Execution list";
          file_put_contents($app->config["base_dir"].$url."/executions/info.json", json_encode($tmp)); 
        }
        if ($routeName == "user")
        {
          mkdir($app->config["base_dir"].$url."/projects");
          $tmp = array();
          $tmp["name"] = "Project list";
          file_put_contents($app->config["base_dir"].$url."/projects/info.json", json_encode($tmp));
        } 
        else if ($routeName == "project") 
        {
          mkdir($app->config["base_dir"].$url."/users");
          $tmp = array();
          $tmp["name"] = "user list";
          file_put_contents($app->config["base_dir"].$url."/users/info.json", json_encode($tmp));
        }
      }
      $info = $data["info"];
      $auth = $data["auth"];
      $auth["password"] = password_hash($auth["login"].$auth["password"], PASSWORD_DEFAULT);
      $app->response->setStatus(STATUS_CREATED);
    }
    file_put_contents($app->config["base_dir"].$url."/info.json",json_encode($info));
    file_put_contents($app->config["base_dir"].$url."/auth.json", json_encode($auth));
    return $is_ok = true;
  }

  public static function delete($url)
  {
    global $app;  
    $is_ok = false;  
    if (file_exists($app->config["base_dir"].$url."/info.json"))
    {
      StreamJson::rrmdir($app->config["base_dir"].$url,$app->config["base_dir"].$url);
      $app->response->setStatus(STATUS_NO_CONTENT);
      $is_ok = true;
    } 
    else 
    {
      $app->response->setStatus(STATUS_NOT_FOUND);
    }
    return $is_ok;
  }

  public static function rrmdir($path, $newPath){
    foreach(glob($newPath . '/*') as $file) 
    {
        if(is_dir($file))
          StreamJson::rrmdir($path, $file);
        else if ($file != $path."/auth.json")
          unlink($file);      
    }
    if($newPath != $path)
      rmdir($newPath);
  }
}
?>