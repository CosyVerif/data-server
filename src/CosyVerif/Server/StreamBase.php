<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class StreamBase
{
  public static function readList($url)
  {
    global $app;
    //Verify existing resource list
    if (!file_exists($app->config["base_dir"].$url))
    {
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    else if(StreamBase::isEmptyDir($app->config["base_dir"].$url))
    {
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $data = json_decode(file_get_contents($app->config["base_dir"].$url."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config["base_dir"].$url.'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue;
      else if (!StreamBase::isEmptyDir($file))
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

  public static function delete($url)
  {
    global $app;  
    $is_ok = false;  
    if (!StreamBase::isEmptyDir($app->config["base_dir"].$url))
    {
      StreamBase::rrmdir($app->config["base_dir"].$url,$app->config["base_dir"].$url);
      $app->response->setStatus(STATUS_NO_CONTENT);
      $is_ok = true;
    } 
    else 
    {
      $app->response->setStatus(STATUS_NOT_FOUND);
    }
    return $is_ok;
  }

  public static function isEmptyDir($url){
    $fileFound = 0;
    if (!is_dir($url))
      return NULL;
    else if ($dh = opendir($url))
    {
      while (($file = readdir($dh)) != false && $fileFound == 0)
      {
        if ($file != "." && $file != "..") 
          $fileFound = 1;
      }
      closedir($dh);
    }
    if ($fileFound == 0) 
      return true;
    else
      return false;
  }

  public static function rrmdir($path, $newPath){
    foreach(glob($newPath . '/*') as $file) 
    {
      if(is_dir($file))
        StreamBase::rrmdir($newPath, $file);
      else
        unlink($file);      
    }
    if($newPath != $path)
      rmdir($newPath);
  }
}
?>