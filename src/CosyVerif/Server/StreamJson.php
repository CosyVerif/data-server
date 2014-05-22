<?php

namespace CosyVerif\Server;
require_once 'Constants.php';


/**
 * JSON response class.
 * 
 * @package core-api
 * 
 */
class StreamJson
{
  public static function read($url)
  {
    global $app;
    //Verify existing resource
    if(!file_exists("resources".$url.".json") && 
       !file_exists("resources".$url.".conf")){
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    }else if(!file_exists("resources".$url.".json") && 
             file_exists("resources".$url.".conf")){
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $resource = json_decode(file_get_contents("resources".$url.".json"), TRUE);
    if(!is_array($resource)){
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
    if(file_exists("resources".$url.".json")){
      // Resource not found
      $resource = json_decode(file_get_contents("resources".$url.".json"), TRUE);
      //Update resource
      foreach ($data as $field => $value) {
        $resource[$field] = $value;
      }
      $app->response->setStatus(STATUS_OK);
    } else{
      $resource = $data;
      file_put_contents("resources".$url.".conf","");
      $app->response->setStatus(STATUS_CREATED);
    }
    file_put_contents("resources".$url.".json",
                      json_encode($resource));
    return $is_ok = true;
  }

  public static function delete($url){
    global $app;    
    if(file_exists("resources".$url.".json")){
      unlink("resources".$url.".json");
      $app->response->setStatus(STATUS_NOT_CONTENT);
      return true;
    }
    $app->response->setStatus(STATUS_NOT_FOUND);
    return false;
  }

  public function getStatus(){return $this->status;}
}
?>