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
    if(!file_exists("resources".$url.".json")){
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    }
    $resource = json_decode(file_get_contents("resources".$url.".json"), TRUE);
    if(!is_array($resource)){
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      return null;
    }    
    if($resource["is_deleted"] == RESOURCE_DELETED){
      $app->response->setStatus(STATUS_GONE);
    } else {
      $app->response->setStatus(STATUS_OK);
    } 
    $app->response->headers->set('Content-Type','application/json');
    return $resource;
  }

  public static function write($url, $data)
  {
    //Write ressource
    $data = json_decode($data);
    if(!is_array($resource)){
      $app->response->setStatus(STATUS_UNPROCESSABLE_ENTITY);
      return false;
    }
    $resource = "";
    if(file_exists("resources".$url.".txt")){
      // Resource not found
      $resource = unserialize(file_get_contents("resources".$url.".json"));
      //TODO update resource
    } else{
      $resource = $data;
    }
    file_put_contents("resources".$url.".txt", serialize($resource));
    return true;
  }

  public function getStatus(){ return $this->status; }

}
?>