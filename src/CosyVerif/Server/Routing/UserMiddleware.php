<?php
namespace CosyVerif\Server\Routing;

class UserMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new UserMiddleware());
  }
  public function call()
  {
    global $app;

    $url = $app->request->getResourceUri();
    $this->app->hook('slim.before.dispatch',  function() use ($app, $url)
    {
      $routeName = $app->router()->getCurrentRoute()->getName();
      if ($routeName == "user")
      {
        $app->resource = UserResource::newResource($url);
      }
    });

    // users router
    $app->get('/users(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    // user router
    $app->get('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->user_read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");
    $app->post('/users/:user(/)', function($user) use($app)
    {
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      else if (!isset($data["request_type"]))
      {
        $app->halt(STATUS_BAD_REQUEST);
      }
      $data["login"] = $user;
      if ($data["request_type"] === "create")
      {
        if (($app->resource->file_exists() && !$app->resource->file_deleted()) || !$app->resource->url_isValid())
          $app->halt(STATUS_CONFLICT);
        $app->resource->user_create($data);
      }
      else if ($data["request_type"] === "activate")
      {
        if (!$app->resource->file_exists())
          $app->halt(STATUS_NOT_FOUND);
        $app->resource->user_activate($data);
      }
      $app->response->setBody("{}");
    })->setName("user");
    $app->put('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->file_exists() || $app->resource->file_deleted())
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->user_write($data);
      $app->response->setBody("{}");
    })->setName("user");
    $app->delete('/users/:user(/)', function() use ($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->user_delete();
    })->setName("user");

    $app->get('/users/:user/projects(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->project_readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    $app->get('/users/:user/invitations(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->read_invitations();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    // user project router
    $app->post('/users/:user/projects/:project(/)', function($user, $project) use($app){
      if (ProjectResource::newResource("/projects/".$project)->file_exists() && 
          !ProjectResource::newResource("/projects/".$project)->file_deleted())
        $app->halt(STATUS_CONFLICT);
      else if (!$app->resource->canCreate($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->project_create($user, $data);
      $app->response->setBody("{}");
    })->setName("user");
    $app->delete('/users/:user/projects/:project(/)', function() use($app){
      if (!$app->resource->link_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->project_delete();
    })->setName("user");
    $app->get('/users/:id/projects/:project(/)', function($id, $project) use($app){
      $url_redirect = $app->resource->project_redirectURL();
      if (is_null($url_redirect))
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->redirect('/server/'.$app->config["main"].$url_redirect, STATUS_PERMANENT_REDIRECT);
    })->setName("user");
    $app->post('/users/:id/projects/:project/:suite+', function($id, $project, $suite) use($app){
      $app->resource->setURL("/users/".$id."/projects/".$project);
      $url_redirect = $app->resource->project_redirectURL();
      if (is_null($url_redirect))
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $url_suite = null;
      if (count($suite) > 0)
        $url_suite = implode('/', array_slice($suite, 0, count($suite)));
      $url_redirect = $url_redirect . ((!is_null($url_suite)) ? "/" . $url_suite : "");
      $app->redirect('/server/'.$app->config["main"].$url_redirect, STATUS_PERMANENT_REDIRECT);
    })->setName("user");
    $app->get('/users/:id/projects/:project/:suite+', function($id, $project, $suite) use($app){
      $app->resource->setURL("/users/".$id."/projects/".$project);
      $url_redirect = $app->resource->project_redirectURL();
      if (is_null($url_redirect))
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $url_suite = null;
      if (count($suite) > 0)
        $url_suite = implode('/', array_slice($suite, 0, count($suite)));
      $url_redirect = $url_redirect . ((!is_null($url_suite)) ? "/" . $url_suite : "");
      $app->redirect('/server/'.$app->config["main"].$url_redirect, STATUS_PERMANENT_REDIRECT);
    })->setName("user");

    $app->put('/users/:id/projects/:project(/|/:name+)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("user");
    $app->patch('/users/:id/projects/:project(/|/:name+)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("user");
    $app->delete('/users/:id/projects/:project/:name+', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("user");

    $this->next->call();
  }
}

class UserResource extends BaseResource
{
  public static function newResource($url)
  {
    return new UserResource($url);
  }

  public function user_create($data)
  {
    global $app;
    $regex_mail = '/^[-+.\w]{1,64}@[-.\w]{1,64}\.[-.\w]{2,6}$/i';
    if (!isset($data["login"]) || !isset($data["password"]) || !isset($data["first_name"]) || !isset($data["last_name"]) || !isset($data['email']))
    {
      $app->response->setStatus(STATUS_BAD_REQUEST);
      return;
    }
    else if (!preg_match($regex_mail, $data['email']))
    {  
      $app->response->setStatus(STATUS_BAD_REQUEST);
      return;
    } 
    $data["name"] = $data["first_name"] . " " . $data["last_name"];
    $auth = array();
    $auth["is_public"] = (array_key_exists("is_public", $data)) ? $data["is_public"] : ($app->config["default_visibility"] === "public");
    $auth["is_admin_user"] =  (array_key_exists("is_admin_user", $data)) ? $data["is_admin_user"] : ($app->config["default_admin_user_authorization"] === "admin");
    $auth["login"] = $data["login"];
    $auth["password"] = password_hash($data["login"].$data["password"], PASSWORD_DEFAULT);
    $auth["validation_key"] = md5(microtime(TRUE)*100000);
    if ($app->config["activate_email_mode"])
      $auth["activate"] = false; // FIXME : false
    else
      $auth["activate"] = true;
    unset($data["is_public"]);
    unset($data["is_admin_user"]);
    unset($data["login"]);
    unset($data["password"]);
    unset($data["request_type"]);
    $json_auth = json_encode($auth);
    $json_info = json_encode($data);
    if(!$this->file_exists())
    {
      mkdir($app->config("base_dir").$this->getURL());
    }
    file_put_contents($app->config("base_dir").$this->getURL()."/auth.json", $json_auth);
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json", $json_info);
    mkdir($app->config("base_dir").$this->getURL()."/formalisms");
    $tmp = array();
    $tmp["name"] = "Formalism list";
    file_put_contents($app->config("base_dir").$this->getURL()."/formalisms/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/models");
    $tmp = array();
    $tmp["name"] = "Model list";
    file_put_contents($app->config("base_dir").$this->getURL()."/models/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/scenarios");
    $tmp = array();
    $tmp["name"] = "scenarios list";
    file_put_contents($app->config("base_dir").$this->getURL()."/scenarios/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/services");
    $tmp = array();
    $tmp["name"] = "Service list";
    file_put_contents($app->config("base_dir").$this->getURL()."/services/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/executions");
    $tmp = array();
    $tmp["name"] = "Execution list";
    file_put_contents($app->config("base_dir").$this->getURL()."/executions/info.json", json_encode($tmp)); 
    mkdir($app->config("base_dir").$this->getURL()."/projects");
    $tmp = array();
    $tmp["name"] = "Project list";
    file_put_contents($app->config("base_dir").$this->getURL()."/projects/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/invitations");
    $tmp = array();
    $tmp["name"] = "Invitation list";
    $tmp["invitations"] = array();
    file_put_contents($app->config("base_dir").$this->getURL()."/invitations/info.json", json_encode($tmp));
    if ($app->config["activate_email_mode"])
    {
      $to = $data['email'];
      $message = "Welcome, <br/><br/><br/>".$app->config["activate_message"]. "<br/><br/><br/>".
      "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['REMOTE_HOST']."/html/activate_account.html?login=".urlencode($auth["login"])."&key=".urlencode($auth["validation_key"]);
      $this->send_message("CosyVerif <systeme-avis@marwata.com>", $to, "Activate your account", "",$message);
    }
    $app->response->setStatus(STATUS_CREATED);
  }

  public function user_activate($data)
  {
    global $app;
    $auth = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/auth.json"), TRUE);
    if ($auth["activate"] == true)
    {
      $app->response->setStatus(STATUS_OK);
      return;
    }
    else if ($auth["validation_key"] === $data["validation_key"])
    {
      $auth["activate"] = true;
      unset($auth["validation_key"]);
      file_put_contents($app->config("base_dir").$this->getURL()."/auth.json", json_encode($auth));
    }
    $app->response->setStatus(STATUS_OK);
  }

  public function user_write($data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $auth = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/auth.json"), TRUE);
    $auth["is_public"] = (array_key_exists("is_public", $data)) ? $data["is_public"] : $auth["is_public"];
    $auth["is_admin_user"] =  (array_key_exists("is_admin_user", $data)) ? $data["is_admin_user"] : $auth["is_admin_user"];
    $auth["password"] = (array_key_exists("password", $data)) ? password_hash($auth["login"].$data["password"], PASSWORD_DEFAULT) : $auth["password"];
    unset($data["is_public"]);
    unset($data["is_admin_user"]);
    unset($data["login"]);
    unset($data["password"]);
    foreach ($data as $field => $value)
    {
      $info[$field] = $value;
    }
    $info["name"] = $info["first_name"] . " " . $info["last_name"];
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json",json_encode($info));
    file_put_contents($app->config("base_dir").$this->getURL()."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_OK);
  }

  public function user_read()
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    if (!is_array($info))
    {
      throw new \Exception("JSON format does not corrects !"); 
    } 
    $auth = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/auth.json"), TRUE);
    if (!is_array($auth))
    {
      throw new \Exception("JSON format does not corrects !"); 
    }  
    $data = $info;
    $data["login"] = $auth["login"];
    $data["is_public"] = $auth["is_public"];
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $data["is_copy"] = false;
    $data["is_move"] = false;
    $data["is_admin_user"] = $auth["is_admin_user"];
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function readList()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config("base_dir").$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue;
      try 
      {
        $tmp = json_decode(file_get_contents($file."/auth.json"), TRUE);
        if ($tmp["activate"] == false)
          continue;
        $tmp = UserResource::newResource($this->getURL()."/".basename($file))->user_read();
        $tmp['href'] = $this->getURL()."/".basename($file);
        $resourceList[] = $this->addInformations($tmp); 
      }
      catch(\Exception $e){ continue; }
    }
    $data["resource_list"] = $resourceList;
    $data["is_create"] = false;
    $data["is_edit"] = false;
    $data["is_delete"] = false;
    $data["is_read"] = true;
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function user_delete()
  {
    global $app;
    $idea_list = array();
    $parts = explode('/', $this->getURL());
    foreach(glob($app->config("base_dir").$this->getURL(). '/projects/*') as $file) 
    {
      if (!is_link($file))
        continue;
      try
      {
        $project_auth = json_decode(file_get_contents($app->config("base_dir").readlink($file)."/auth.json"), TRUE);
        $permissions = $project_auth["users"];
        unset($permissions[$parts[2]]);
        $project_auth["users"] = $permissions;
        if (count($permissions) <= 0)
        {
          ProjectResource::newResource(readlink($file))->project_delete();
          continue;
        }
        unlink($app->config("base_dir").readlink($file)."/users/" . $parts[2]);
        file_put_contents($app->config("base_dir").readlink($file)."/auth.json",json_encode($project_auth));
      }
      catch(\Exception $e){ continue; }
    }
    $user_invitation_info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/invitations/info.json"), TRUE);
    $user_invitations = $user_invitation_info["invitations"];
    foreach ($user_invitations as $key => $value) 
    {
      if ($value["status"] == STATUS_SEND || $value["status"] == STATUS_RECEIVED) 
      {
        $project_invitation_info = json_decode(file_get_contents($app->config("base_dir").$value["project_url"]."/invitations/info.json"), TRUE);
        $project_invitations = $project_invitation_info["invitations"];
        $invitation_data = $project_invitations[$key];
        $invitation_data["status"] = STATUS_DENIED;
        $invitation_data["comment"] = "User is deleted !";
        $invitation_data["requesting_user"] = $app->user["login"];
        $project_invitations[$key] = $invitation_data;
        $project_invitation_info["invitations"] = $project_invitations;
        file_put_contents($app->config("base_dir").$value["project_url"]."/invitations/info.json", json_encode($project_invitation_info));
      }
    }
    $this->delete_dir();
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function project_readList()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config("base_dir").$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_link($file))
        continue;
      try
      {
        $project_url = readlink($file);
        $tmp = ProjectResource::newResource($project_url)->project_read();
        if (!is_null($tmp))
        {
          $tmp["href"] = $project_url;
          $tmp["is_create"] = BaseResource::newResource($this->getURL()."/".basename($file))->canCreate($app->user);
          $tmp["is_edit"] = BaseResource::newResource($this->getURL()."/".basename($file))->canWrite($app->user);
          $tmp["is_delete"] = BaseResource::newResource($this->getURL()."/".basename($file))->canDelete($app->user);
          $tmp["is_read"] = BaseResource::newResource($this->getURL()."/".basename($file))->canRead($app->user);
          $resourceList[] = $this->addInformations($tmp); 
        }
      }
      catch(\Exception $e){ continue; }
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

  public function project_create($user, $data)
  {
    global $app;
    $parts = explode('/', $this->getURL());
    $project_name = $parts[count($parts) - 1];
    $project = ProjectResource::newResource("/projects/" . $project_name);
    if ($project->project_create($data) == FALSE)
      return; 
    symlink("/projects/" . $project_name, $app->config("base_dir").$this->getURL());
    symlink("/users/" . $user, $app->config("base_dir")."/projects/" . $project_name ."/users/". $user);
    $auth = json_decode(file_get_contents($app->config("base_dir")."/projects/".$project_name."/auth.json"), TRUE);
    $auth["users"] = array($user => array('is_admin_project' => true, 'is_edit_project' => true));
    file_put_contents($app->config("base_dir")."/projects/".$project_name."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_CREATED);
  }

  public function project_delete()
  {
    global $app;
    $parts = explode('/', $this->getURL());
    $user = $parts[2];
    $project = $parts[count($parts) - 1];
    unlink($app->config("base_dir")."/users/".$user."/projects/".$project);
    unlink($app->config("base_dir")."/projects/".$project."/users/".$user);
    $auth = json_decode(file_get_contents($app->config("base_dir")."/projects/".$project."/auth.json"), TRUE);
    $users = $auth["users"];
    unset($users[$user]);
    $auth["users"] = $users;
    file_put_contents($app->config("base_dir")."/projects/".$project."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function project_redirectURL()
  {
    global $app;
    if (!is_link($app->config("base_dir").$app->resource->getURL()))
      return NULL;
    else
      return readlink($app->config("base_dir").$this->getURL());
  }
}