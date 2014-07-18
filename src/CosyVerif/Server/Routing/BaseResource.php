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

  public function readList()
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    //Verify existing resource list
    if (!file_exists($app->config["base_dir"].$this->url))
    {
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    else if($this->isEmptyDir($app->config["base_dir"].$this->url))
    {
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $data = json_decode(file_get_contents($app->config["base_dir"].$this->url."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config["base_dir"].$this->url.'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue;
      else if (!$this->isEmptyDir($file))
      { 
        $tmp = json_decode(file_get_contents($file."/info.json"), TRUE);
        $resourceList[] = array('href' => $this->url."/".basename($file), 'name' => $tmp["name"]);
      }
    }
    $data["resource_list"] = $resourceList;
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $data;
  }

  public function delete()
  {
    global $app;  
    $is_ok = false;  
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!$this->isEmptyDir($app->config["base_dir"].$this->url))
    {
      $this->rrmdir($app->config["base_dir"].$this->url,$app->config["base_dir"].$this->url);
      $app->response->setStatus(STATUS_NO_CONTENT);
      $is_ok = true;
    } 
    else 
    {
      $app->response->setStatus(STATUS_NOT_FOUND);
    }
    return $is_ok;
  }

  public function isPublic()
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    $parts = explode('/', $this->url);
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/'.$this->getURLBase().'/'.$parts[2]."/auth.json"), TRUE);
    return ($auth["can_public"] ==  IS_PUBLIC);
  }

  public function isPrivate()
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/'.$this->getURLBase().'/'.$parts[2]."/auth.json"), TRUE);
    return ($auth["can_public"] !=  IS_PUBLIC);
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
    else if ($this->isOwner($user))
    {
      return true;
    }
    else if ($user["login"] == $app->config["user_root"])
    {
      $parts = explode('/', $this->url);
      return ($this->getURLBase() == "users" && count($parts) == 3);
    }
    else if ($this->getURLBase() == "users")
    {
      return $this->isPublic();
    } 
    else  if ($this->getURLBase() == "projects")
    {
      return ($this->canParticipate($user) || $this->isPublic());
    } 
    else
      return false;
  }

  public function canWrite($user)
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
    else if ($this->isOwner($user))
    {
      return true;
    }
    else if ($user["login"] == $app->config["user_root"])
    {
      $parts = explode('/', $this->url);
      return ($this->getURLBase() == "users" && count($parts) == 3);
    }
    else if ($this->getURLBase() == "users")
    { /* Pser resources */
      $parts = explode('/', $this->url);

      if (count($parts) == 3) /* is resource user */
        return $this->canAdmin($user);
      else
        false; 
    } 
    else if ($this->getURLBase() == "projects")
    { /* Project resources */
      if (!$this->canParticipate($user))
        return false;
      $parts = explode('/', $this->url);
      if (count($parts) == 3 || (count($parts) == 5 && $parts[3] == "users"))
        return $this->canAdmin($user);
      else
      {
        $parts = explode('/', $this->url);
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
        $users = $auth["users"];
        $permissions = $users[$user["login"]];
        return ($permissions["edit_project"]);
      }
    } 
    else
      return false;
  }

  public function canAdmin($user)
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

  public function isOwner($user)
  {
    global $app;
    if ($this->isRootURL())
    {
      return false;
    }
    else if ($this->getURLBase() == "users")
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
    if (is_null($user))
      return false;
    $parts = explode('/', $this->url);
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/projects/'.$parts[2]."/auth.json"), TRUE);
    $users = $auth["users"];
    return (array_key_exists($user["login"], $users));
  }

  public function exists()
  {
    global $app;
    return file_exists($app->config["base_dir"].$this->url);
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