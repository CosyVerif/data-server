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
    $resource = "";
    if (file_exists("resources".$url."/info.json")){
      // Resource not found
      $resource = json_decode(file_get_contents("resources".$url."/info.json"), TRUE);
      //Update resource
      foreach ($data as $field => $value) {
        $resource[$field] = $value;
      }
      $app->response->setStatus(STATUS_OK);
    } else{
      if(!file_exists("resources".$url))
        mkdir("resources".$url);
      $resource = $data;
      $password = $resource["login"].$resource["password"];
      $auth = array('login' => $resource["login"], 
                    'password' => password_hash($password, PASSWORD_DEFAULT));
      file_put_contents("resources".$url."/auth.json", json_encode($auth));
      $app->response->setStatus(STATUS_CREATED);
    }
    unset($resource["login"]);
    unset($resource["password"]);
    file_put_contents("resources".$url."/info.json",
                      json_encode($resource));
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