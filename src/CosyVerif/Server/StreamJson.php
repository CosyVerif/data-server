<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class StreamJson
{
  public static function read($url)
  {
    global $app;
    //Verify existing resource
    if (!file_exists("resources".$url)){
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } else if(!file_exists("resources".$url."/info.json")){
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $resource = json_decode(file_get_contents("resources".$url."/info.json"), TRUE);
    if (!is_array($resource)){
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      return null;
    }    
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $resource;
  }

  public static function write($url, $data)
  {
    global $app;
    //Write ressource
    $is_ok = false;
    $info = "";
    $auth = "";
    if (file_exists("resources".$url."/info.json")){
      // Resource not found
      $info = json_decode(file_get_contents("resources".$url."/info.json"), TRUE);
      //Update resource
      foreach ($data["info"] as $field => $value){
        $info[$field] = $value;
      }
      $auth = json_decode(file_get_contents("resources".$url."/auth.json"), TRUE);
      //Update resource
      foreach ($data["auth"] as $field => $value){
        $info[$field] = $value;
      }
      $app->response->setStatus(STATUS_OK);
    } else {
      if(!file_exists("resources".$url))
        mkdir("resources".$url);
      $info = $data["info"];
      $auth = $data["auth"];
      $auth["password"] = password_hash($auth["login"].$auth["password"], PASSWORD_DEFAULT);
      $app->response->setStatus(STATUS_CREATED);
    }
    file_put_contents("resources".$url."/info.json",json_encode($info));
    file_put_contents("resources".$url."/auth.json", json_encode($auth));
    return $is_ok = true;
  }

  public static function delete($url)
  {
    global $app;  
    $is_ok = false;  
    if (file_exists("resources".$url."/info.json")){
      unlink("resources".$url."/info.json");
      $app->response->setStatus(STATUS_NO_CONTENT);
      $is_ok = true;
    } else {
      $app->response->setStatus(STATUS_NOT_FOUND);
    }
    return is_ok;
  }
}
?>