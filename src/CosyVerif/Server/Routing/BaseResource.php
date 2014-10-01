<?php

namespace CosyVerif\Server\Routing;

class BaseResource
{
  private $url;

  function __construct($url)
  {
    $this->url = $url;
  }

  public static function newResource($url)
  {
    return new BaseResource($url);
  }

  public function getURL(){ return $this->url; }
  public function setURL($url){ $this->url = $url; }

  public function url_isValid()
  {
    $parts = explode('/', $this->getURL());
    $resource_name = $parts[count($parts) - 1];
    $pattern = "#[^,|\\^~\[\]`\#;/\?:@=&]#";
    return preg_match($pattern, $resource_name);
  }

  public function initializes_server()
  {
    global $app;
    $files = array_diff(scandir($app->config["base_dir"]), array('.','..'));
    foreach ($files as $file) 
    {
      (is_dir($app->config["base_dir"]."/$file")) ? $this->rrmdir($app->config["base_dir"]."/$file") : unlink($app->config["base_dir"]."/$file");
    }
    $info = array('name' => 'Root base');
    $auth = array('login' => $app->config["user_root"],
                  'password' => password_hash($app->config["user_root"].'toto', PASSWORD_DEFAULT));
    file_put_contents($app->config["base_dir"]."/info.json", json_encode($info));
    mkdir($app->config["base_dir"]."/users");
    $info['name'] = "User list";
    file_put_contents($app->config["base_dir"]."/users/info.json", json_encode($info));
    mkdir($app->config["base_dir"]."/projects");
    $info['name'] = "Project list";
    file_put_contents($app->config["base_dir"]."/projects/info.json", json_encode($info));
    mkdir($app->config["base_dir"]."/users/".$app->config["user_root"]);
    $info['name'] = "Root base";
    file_put_contents($app->config["base_dir"]."/users/".$app->config["user_root"]."/info.json", json_encode($info));
    file_put_contents($app->config["base_dir"]."/users/".$app->config["user_root"]."/auth.json", json_encode($auth));
  }

  public function readList()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config["base_dir"].$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue; 
      try
      {
        $tmp = json_decode(file_get_contents($file."/info.json"), TRUE);
        $resourceList[] = array('href' => $this->getURL()."/".basename($file), 'name' => $tmp["name"], 'description' => $tmp["description"]);
      }
      catch (\Exception $e){ continue; }
    }
    $data["resource_list"] = $resourceList;
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function read_invitations()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    if (!is_array($data))
    {
      throw new \Exception("JSON format does not corrects !"); 
    }
    $tmp = array();
    foreach ($data["invitations"] as $key => $value) 
    {
      if ((($app->request->params("send") == "true" && $value["status"] == "send") ||
           ($app->request->params("received") == "true" && $value["status"] == "received") ||
           ($app->request->params("ack") == "true" && $value["status"] == "ack") ||
           ($app->request->params("denied") == "true" && $value["status"] == "denied") ||
           ($app->request->params("canceled") == "true" && $value["status"] == "canceled")) &&
           $value["view"] == false)
      {
        $userData = UserResource::newResource("/users/".$value["requesting_user"])->user_read();
        $value["name"] = $userData["name"];
        $parts = explode('/', $value["project_url"]);
        $value["href"] = ($value["added_user"] == $value["requesting_user"]) ? $value["project_url"]."/invitations/".$value["added_user"] : "/users/".$value["added_user"]."/invitations/".$parts[2];
        $value["timestamp"] = $key;
        $tmp[$key] = $value; 
      }
    } 
    $data["invitations"] = $tmp;
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function delete_dir()
  {
    global $app;   
    $this->rrmdir($app->config["base_dir"].$this->url);
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function delete_file()
  {
    global $app;  
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
      if (!$this->isActive())
        return false;
      else if (count($parts) == 3)
      {
        return ($this->isOwner($user) || $user["login"] == $app->config["user_root"] || $this->canAdmin($user));
      }
      else
        return ($this->isOwner($user));
    }
    else if ($this->getURLBase() == "projects")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 3)
      {
        return ($this->canAdmin($user));
      }
      else if (count($parts) == 5 && ($parts[3] == "users" || $parts[3] == "invitations"))
      { 
        return ($this->canAdmin($user));
      }
      else
      {
        if (!$this->canParticipate($user)){ return false; }
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
        $users = $auth["users"];
        $permissions = $users[$user["login"]];
        return ($permissions["is_edit_project"]);
      }
    }
    else
      return false;
  }

  public function canWrite($user)
  {
    global $app;
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
      if (count($parts) == 3)
      {
        return ($this->canAdmin($user));
      }
      else if (count($parts) == 5 && $parts[3] == "users")
      {
        return ($this->canAdmin($user) || $parts[4] == $user["login"]);
      }
      else if (count($parts) == 5 && $parts[3] == "invitations")
      {
        return ($this->canAdmin($user));
      }
      else
      {
        if (!$this->canParticipate($user)){ return false; }
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
        $users = $auth["users"];
        $permissions = $users[$user["login"]];
        return ($permissions["is_edit_project"]);
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
      if (!$this->isActive())
        return false;
      else if (count($parts) == 3 && $user["login"] == $app->config["user_root"])
        return true;
      else 
        return ($this->isOwner($user) || $this->isPublic());
    }
    else if ($this->getURLBase() == "projects")
    {
      $parts = explode('/', $this->url);
      if (count($parts) == 5 && $parts[3] == "invitations")
      {
        return ($this->canAdmin($user));
      }
      else
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
    return ($auth["is_public"] ==  IS_PUBLIC);
  }

  private function isActive()
  {
    global $app;
    $parts = explode('/', $this->url);
    $auth = json_decode(file_get_contents($app->config["base_dir"].'/users/'.$parts[2]."/auth.json"), TRUE);
    return $auth["activate"];
  }

  private function canAdmin($user)
  {
    global $app;
    if ($this->getURLBase() == "users")
    {
      $auth = json_decode(file_get_contents($app->config["base_dir"]."/users/".$user["login"]."/auth.json"), TRUE);
      return ($auth["is_admin_user"]);
    }
    else  if ($this->getURLBase() == "projects")
    { 
      if (!$this->canParticipate($user))
        return false;
      $parts = explode('/', $this->url);
      $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$parts[2]."/auth.json"), TRUE);
      $users = $auth["users"];
      $permissions = $users[$user["login"]];
      return ($permissions["is_admin_project"]);
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

  public function getURLBase()
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

  public function file_exists()
  {
    global $app;
    return (file_exists($app->config["base_dir"].$this->url));
  }

  public function file_deleted()
  {
    global $app;
    $files = array_diff(scandir($app->config["base_dir"].$this->url), array('.','..'));
    foreach ($files as $file) 
    {
     return false;
    }
    return true;
  }

  public function link_exists()
  {
    global $app;
    return (is_link($app->config["base_dir"].$this->url));
  }

  public function send_message($from, $to, $subject, $link_header, $message)
  {
    $to = implode(",", $to);
    $subject = $subject;
    $headers = 'From: '.$from."\r\n";
    $headers .= 'Mime-Version: 1.0'."\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8'."\r\n";
    $headers .= "\r\n";
    $msg = $message.'<br/><br/><br/>
            ------------------ <br/>
            This is an automated email, please do not respond.';
     
    mail($to, $subject, $msg, $headers);
  }

  public function rrmdir($dir){
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      if (is_dir("$dir/$file"))
      {
        $this->rrmdir("$dir/$file");
        rmdir("$dir/$file");
      }
      else
        unlink("$dir/$file");
    }
  }

  public function addInformations($resource)
  {
    $parts = explode('/', $resource["href"]);
    $resource["logo"] = ($parts[1] == "users");
    $resource["project"] = $parts[2];
    if (count($parts) >= 3)
    {
      $resource["logo"] = ($parts[1] == "users");
      if ($parts[1] == "users")
      {
        $resource["logo"] = true;
        $user = UserResource::newResource("/users/".$parts[2])->user_read();
        $resource["project"] = $user["name"];
      }
      else
      {
        $resource["logo"] = false;
        $project = ProjectResource::newResource("/projects/".$parts[2])->project_read();
        $resource["project"] = $project["name"];
      }
    }
    if (count($parts) == 3)
    {
      if ($parts[1] == "users")
      {
        $resource["type"] = "User";
        $resource["color"] = "color-user";
      }
      else
      {
        $resource["type"] = "Project";
        $resource["color"] = "color-project";
      }
    }
    else if (count($parts) == 5)
    {
      if ($parts[3] == "formalisms")
      {
        $resource["type"] = "Formalism";
        $resource["color"] = "color-formalism";
      }
      else if ($parts[3] == "models")
      {
        $resource["type"] = "Model";
        $resource["color"] = "color-model";
      }  
      else if ($parts[3] == "services")
      {
        $resource["type"] = "Service";
        $resource["color"] = "color-service";
      }
      else if ($parts[3] == "executions")
      {
        $resource["type"] = "Execution";
        $resource["color"] = "color-execution";
      } 
      else if ($parts[3] == "scenarios")
      {
        $resource["type"] = "Scenario";
        $resource["color"] = "color-scenario";
      }   
    }
    return $resource;  
  }

}
