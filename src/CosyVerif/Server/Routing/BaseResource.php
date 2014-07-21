<?php

namespace CosyVerif\Server\Routing;

class BaseResource
{
  private $url;

  function __construct($url)
  {
    $this->url = $url;
  }

  public function getURL(){ return $this->url; }
  public function setURL($url){ $this->url = $url; }

  public function delete_dir()
  {
    global $app;   
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $this->rrmdir($app->config["base_dir"].$this->url,$app->config["base_dir"].$this->url);
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function delete_file()
  {
    global $app;   
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    unlink($app->config["base_dir"].$this->url);
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function canCreate($user)
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    else if (is_null($user))
    {
      return false;
    } 
    else if ($this->getURLBase() == "users")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3)
      {
        return ($this->isOwner($user) || $user["login"] == $app->config["user_root"] || $this->canAdmin($user));
      }
      else
        return ($this->isOwner($user));
    }
    else if ($this->getURLBase() == "projects")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3 || (count($parts) == 5 && $parts[3] == "users"))
      {
        return ($this->canAdmin($user));
      }
      else
      {
        if (!$this->canParticipate($user)){ return false; }
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
        $users = $auth["users"];
        $permissions = $users[$user["login"]];
        return ($permissions["edit_project"]);
      }
    }
    else
      return false;
  }

  public function canWrite($user)
  {
    return $this->canCreate($user);
  }

  public function canDelete($user)
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    else if (is_null($user))
    {
      return false;
    } 
    else if ($this->getURLBase() == "users")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3)
      {
        return ($this->isOwner($user) || $user["login"] == $app->config["user_root"] || $this->canAdmin($user));
      }
      else
        return ($this->isOwner($user));
    }
    else if ($this->getURLBase() == "projects")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3 || (count($parts) == 5 && $parts[3] == "users"))
      {
        return ($this->canAdmin($user) || $user == $parts[4]);
      }
      else
      {
        if (!$this->canParticipate($user)){ return false; }
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
        $users = $auth["users"];
        $permissions = $users[$user["login"]];
        return ($permissions["edit_project"]);
      }
    }
    else
      return false;
  }

  public function canRead($user)
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    else if (is_null($user))
    {
      return $this->isPublic();
    }
    else if ($this->getURLBase() == "users")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3 && $user["login"] == $app->config["user_root"])
        return true;
      else 
        return ($this->isOwner($user) || $this->isPublic());
    }
    else if ($this->getURLBase() == "projects")
    {
      return ($this->canParticipate($user) || $this->isPublic());
    }
    else
      return false;
  }

  private function isPublic()
  {
    global $app;
    $parts = explode('/', $this->url);
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/'.$this->getURLBase().'/'.$parts[2]."/auth.json"), TRUE);
    return ($auth["can_public"] ==  IS_PUBLIC);
  }

  private function canAdmin($user)
  {
    global $app;
    if ($this->getURLBase() == "users")
    {
      $auth = json_decode(file_get_contents($app->config["base_dir"]."/users/".$user["login"]."/auth.json"), TRUE);
      return ($auth["admin_user"]);
    }
    else  if ($this->getURLBase() == "projects")
    {
      if (!$this->canParticipate($user))
        return false;
      $parts = explode('/', $this->url);
      $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
      $users = $auth["users"];
      $permissions = $users[$user["login"]];
      return ($permissions["admin_project"]);
    }
    else 
      return false;
  }

  private function isOwner($user)
  {
    global $app;
    if ($this->getURLBase() == "users")
    {
      $parts = explode('/', $this->url);
      return ($user["login"] == $parts[2]);
    }
    else
      return false;
  }

  private function getURLBase()
  {
    $parts = explode('/', $this->url);
    return $parts[1];
  }

  private function isRootURL()
  {
    global $app;
    $parts = explode('/', $this->url);
    if ($this->getURLBase() == "users" && $parts[2] == $app->config["user_root"])
      return true;
    else
      return false;
  }

  private function canParticipate($user)
  {
    global $app;
    $parts = explode('/', $this->url);
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/projects/'.$parts[2]."/auth.json"), TRUE);
    $users = $auth["users"];
    return (array_key_exists($user["login"], $users));
  }

  public function exists()
  {
    global $app;
    return (file_exists($app->config["base_dir"].$this->url));
  }

  public function deleted()
  {
    global $app;
    return ($this->isEmptyDir($app->config["base_dir"].$this->url));
  }

  public function isEmptyDir($url){
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

  public function rrmdir($path, $newPath){
    foreach(glob($newPath . '/*') as $file) 
    {
      if(is_dir($file))
        $this->rrmdir($newPath, $file);
      else
        unlink($file);      
    }
    if($newPath != $path)
      rmdir($newPath);
  }
}
?>