<?php

namespace CosyVerif\Server\Routing;

class ProjectMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new ProjectMiddleware());
  }
  public function call()
  {
    global $app;

    $url = $app->request->getResourceUri();
    $this->app->hook('slim.before.dispatch',  function() use ($app, $url)
    {
      $routeName = $app->router()->getCurrentRoute()->getName();
      if ($routeName == "project")
      {
        $app->resource = ProjectResource::newResource($url);
      }

    });

    // users router
    $app->get('/projects(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");

    // user router
    $app->get('/projects/:project(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->project_read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");
    $app->put('/projects/:project(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->project_write($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->delete('/projects/:project(/)', function() use ($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->project_delete();
    })->setName("project");

    $app->get('/projects/:project/users(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->user_readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");

    $app->get('/projects/:project/invitations(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->read_invitations();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");
    
    $app->post('/projects/:project/invitations/:user(/)', function($project, $user) use($app){
      if (!file_exists($app->config("base_dir").'/projects/'.$project) || 
          !file_exists($app->config("base_dir").'/users/'.$user))
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canCreate($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $data["project_url"] = "/projects/$project";
      $data["added_user"] = $user;
      $data["requesting_user"] = $app->user["login"];
      $app->resource->invitation_create($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->put('/projects/:project/invitations/:user(/)', function($project, $user) use($app){
      if (!file_exists($app->config("base_dir").'/projects/'.$project) || 
          !file_exists($app->config("base_dir").'/users/'.$user))
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $data["project_url"] = "/projects/$project";
      $data["added_user"] = $user;
      $data["requesting_user"] = $app->user["login"];
      $app->resource->invitation_update($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->delete('/projects/:project/invitations(/)', function($project) use($app){
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->invitation_delete();
    })->setName("project");
    $app->post('/users/:user/invitations/:project(/)', function($user, $project) use($app){
      if (!file_exists($app->config("base_dir").'/projects/'.$project) || 
          !file_exists($app->config("base_dir").'/users/'.$user))
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canCreate($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $data["project_url"] = "/projects/$project";
      $data["added_user"] = $user;
      $data["requesting_user"] = $user;
      $app->resource->invitation_create($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->put('/users/:user/invitations/:project(/)', function($user, $project) use($app){
      if (!file_exists($app->config("base_dir").'/projects/'.$project) || 
          !file_exists($app->config("base_dir").'/users/'.$user))
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $data["project_url"] = "/projects/$project";
      $data["added_user"] = $user;
      $data["requesting_user"] = $user;
      $app->resource->invitation_update($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->delete('/users/:user/invitations(/)', function($user) use($app){
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->invitation_delete();
    })->setName("project");
    $app->put('/projects/:project/users/:user(/)', function($project, $user) use($app){
      if (!$app->resource->link_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->changePermissions($data);
      $app->response->setBody("{}");
    })->setName("project");
    $app->delete('/projects/:project/users/:user(/)', function($project, $user) use($app){
      if (!$app->resource->link_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->user_delete();
    })->setName("project");
    $app->get('/projects/:id/users/:user(/)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 
    $app->put('/projects/:id/users/:user(/)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project");  
    $app->delete('/projects/:id/users/:user(/)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 
    $app->get('/projects/:id/users/:user/:other+', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 
    $app->put('/projects/:id/users/:user/:other+', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 
    $app->patch('/projects/:id/users/:user/:other+', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 
    $app->delete('/projects/:id/users/:user/:other+', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
    })->setName("project"); 

    $this->next->call();
  }
}

class ProjectResource extends BaseResource
{
  public static function newResource($url)
  {
    return new ProjectResource($url);
  }

  public function project_create($data)
  {
    global $app;
    if (!array_key_exists("name", $data))
    {
      $app->response->setStatus(STATUS_BAD_REQUEST);
      return FALSE;
    }
    $auth = array();
    $auth["is_public"] = (array_key_exists("is_public", $data)) ? $data["is_public"] : ($app->config["default_visibility"] === "public");
    unset($data["is_public"]);
    $auth["users"] = array();
    $json_auth = json_encode($auth);
    $json_info = json_encode($data);
    if(!$this->file_exists())
    {
      mkdir($app->config("base_dir").$this->getURL());
    }
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json", $json_info);
    file_put_contents($app->config("base_dir").$this->getURL()."/auth.json", $json_auth);
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
    $tmp = array();
    mkdir($app->config("base_dir").$this->getURL()."/executions");
    $tmp = array();
    $tmp["name"] = "Execution list";
    file_put_contents($app->config("base_dir").$this->getURL()."/executions/info.json", json_encode($tmp)); 
    mkdir($app->config("base_dir").$this->getURL()."/users");
    $tmp = array();
    $tmp["name"] = "user list";
    file_put_contents($app->config("base_dir").$this->getURL()."/users/info.json", json_encode($tmp));
    mkdir($app->config("base_dir").$this->getURL()."/invitations");
    $tmp = array();
    $tmp["name"] = "Invitation list";
    $tmp["invitations"] = array();
    file_put_contents($app->config("base_dir").$this->getURL()."/invitations/info.json", json_encode($tmp));
    $app->response->setStatus(STATUS_CREATED);
    return TRUE;
  }

  public function project_write($data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $auth = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/auth.json"), TRUE);
    $auth["is_public"] = (array_key_exists("is_public", $data)) ? $data["is_public"] : $auth["is_public"];
    unset($data["is_public"]);
    foreach ($data as $field => $value)
    {
      $info[$field] = $value;
    }
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json",json_encode($info));
    file_put_contents($app->config("base_dir").$this->getURL()."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_OK);
  }

  public function project_read()
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
    $data["is_public"] = $auth["is_public"]; 
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $data["is_copy"] = false;
    $data["is_move"] = false;
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
        $tmp = ProjectResource::newResource($this->getURL()."/".basename($file))->project_read();
        $tmp['href'] = $this->getURL()."/".basename($file);
        $resourceList[] = $this->addInformations($tmp); 
      }
      catch (\Exception $e){ continue; }
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

  public function project_delete()
  {
    global $app;
    $project_invitation_info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/invitations/info.json"), TRUE);
    $project_invitations = $project_invitation_info["invitations"];
    $project_auth = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/auth.json"), TRUE);
    $permissions = $project_auth["users"];
    if (count($permissions) > 0) 
    {
      foreach ($permissions as $key => $value) 
      {
        unlink($app->config("base_dir")."/users/".$key.$this->getURL());
      }
    }
    foreach ($project_invitations as $key => $value) 
    {
      if ($value["status"] == STATUS_SEND || $value["status"] == STATUS_RECEIVED) 
      {
        $user_invitation_info = json_decode(file_get_contents($app->config("base_dir")."/users/".$value["added_user"]."/invitations/info.json"), TRUE);
        $user_invitations = $user_invitation_info["invitations"];
        $invitation_data = $user_invitations[$key];
        $invitation_data["status"] = STATUS_DENIED;
        $invitation_data["comment"] = "Project is deleted !";
        $invitation_data["requesting_user"] = $app->user["login"];
        $user_invitations[$key] = $invitation_data;
        $user_invitation_info["invitations"] = $user_invitations;
        file_put_contents($app->config("base_dir")."/users/".$value["added_user"]."/invitations/info.json", json_encode($user_invitation_info));
      }
    }
    $this->delete_dir();
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function user_readList()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    $parts = explode('/', $this->getURL());
    $projectName = $parts[2];
    $auth = json_decode(file_get_contents($app->config("base_dir")."/projects/".$projectName."/auth.json"), TRUE);
    $users = $auth["users"];
    foreach (glob($app->config("base_dir").$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_link($file))
        continue;
      try
      {
        $user_url = readlink($file);
        $tmp = UserResource::newResource($user_url)->user_read();
        if (!is_null($tmp))
        {
          $tmp["href"] = $user_url;
          $projectUser = UserResource::newResource("/projects/".$projectName.$user_url);
          $tmp["is_create"] = $projectUser->canCreate($app->user);
          $tmp["is_edit"] = $projectUser->canWrite($app->user);
          $tmp["is_delete"] = $projectUser->canDelete($app->user);
          $parts = explode('/', $user_url);
          $user = $users[$parts[2]];
          $tmp["is_admin_project"] = $user["is_admin_project"];
          $tmp["is_edit_project"] = $user["is_edit_project"];
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

  public function invitation_create($data)
  {
    global $app;
    if (ProjectResource::newResource($data["project_url"]."/users/".$data["added_user"])->link_exists())
    {
      $app->response->setStatus(STATUS_CONFLICT);
      return NULL;
    }
    $user_info = json_decode(file_get_contents($app->config("base_dir")."/users/".$data["added_user"]."/invitations/info.json"), TRUE);
    $user_invitations = $user_info["invitations"];
    $project_info = json_decode(file_get_contents($app->config("base_dir").$data["project_url"]."/invitations/info.json"), TRUE);
    $project_invitations = $project_info["invitations"];
    foreach ($user_invitations as $key => $value) 
    {
      if ($value["project_url"] === $data["project_url"] && $value["added_user"] === $data["added_user"] && 
        ($value["status"] == STATUS_SEND || $value["status"] == STATUS_RECEIVED)) 
      {
        $app->response->setStatus(STATUS_CONFLICT);
        return NULL;
      }
    }
    $timestamp = time();
    $data["view"] = false;
    if ($app->user["login"] === $data["added_user"])
    {
      $data["status"] = STATUS_SEND;
      $user_invitations[$timestamp] = $data;
      $data["status"] = STATUS_RECEIVED;
      $project_invitations[$timestamp] = $data;
    } 
    else 
    {
      $data["status"] = STATUS_RECEIVED;
      $user_invitations[$timestamp] = $data;
      $data["status"] = STATUS_SEND;
      $project_invitations[$timestamp] = $data;
    }
    $user_info["invitations"] = $user_invitations;
    $project_info["invitations"] = $project_invitations;
    file_put_contents($app->config("base_dir")."/users/".$data["added_user"]."/invitations/info.json", json_encode($user_info));
    file_put_contents($app->config("base_dir").$data["project_url"]."/invitations/info.json", json_encode($project_info));
    $app->response->setStatus(STATUS_CREATED);
  }

  public function invitation_update($data)
  {
    global $app;
    $user_info = json_decode(file_get_contents($app->config("base_dir")."/users/".$data["added_user"]."/invitations/info.json"), TRUE);
    $user_invitations = $user_info["invitations"];
    $project_info = json_decode(file_get_contents($app->config("base_dir").$data["project_url"]."/invitations/info.json"), TRUE);
    $project_invitations = $project_info["invitations"];
    $invitation_user_data = $user_invitations[$data["timestamp"]];
    $invitation_project_data = $project_invitations[$data["timestamp"]];
    if ((($invitation_user_data["status"] != STATUS_SEND && $invitation_user_data["status"] != STATUS_RECEIVED) ||
         ($app->user["login"] === $data["added_user"] && $invitation_user_data["status"] != STATUS_RECEIVED) ||
         ($app->user["login"] !== $data["added_user"] && $invitation_user_data["status"] != STATUS_SEND)) && 
        ($data["status"] != STATUS_COMMENT && $data["status"] != STATUS_VIEW))
    {
      $app->response->setStatus(STATUS_FORBIDDEN);
      return NULL;
    } 
    if ($data["status"] == STATUS_VIEW)
    {
      $userView = true;
      $projectView = true;
    }
    else if ($app->user["login"] === $data["added_user"] && $data["status"] == STATUS_COMMENT)
    {
      $userView = true;
      $projectView = false;
    }
    else if ($app->user["login"] !== $data["added_user"] && $data["status"] == STATUS_COMMENT)
    {
      $userView = false;
      $projectView = true;
    }
    else if ($app->user["login"] === $data["added_user"])
    {
      $userView = true;
      $projectView = false;
    }
    else 
    {
      $userView = false;
      $projectView = true;
    }
    $timestamp = $data["timestamp"];
    unset($data["timestamp"]);
    $data["view"] = $userView;
    $data_status = $data["status"];
    $data["status"] = ($data["status"] == STATUS_COMMENT || $data["status"] == STATUS_VIEW) ? $invitation_user_data["status"] : $data["status"];
    $data["comment"] = ($data["status"] == STATUS_VIEW) ? $invitation_user_data["comment"] : $data["comment"];
    $user_invitations[$timestamp] = $data;
    $user_info["invitations"] = $user_invitations;
    $data["view"] = $projectView;
    $data["status"] = ($data["status"] == STATUS_COMMENT || $data["status"] == STATUS_VIEW) ? $invitation_project_data["status"] : $data["status"];
    $data["comment"] = ($data["status"] == STATUS_VIEW) ? $invitation_project_data["comment"] : $data["comment"];
    $project_invitations[$timestamp] = $data;
    $project_info["invitations"] = $project_invitations;
    file_put_contents($app->config("base_dir")."/users/".$data["added_user"]."/invitations/info.json", json_encode($user_info));
    file_put_contents($app->config("base_dir").$data["project_url"]."/invitations/info.json", json_encode($project_info));
    if ($data_status == STATUS_ACK)
    {
      $project_auth = json_decode(file_get_contents($app->config("base_dir").$data["project_url"]."/auth.json"), TRUE);
      $permissions = $project_auth["users"];
      $permissions[$data["added_user"]] = array('is_admin_project' => ($app->config["default_admin_project_authorization"] === "admin"), 'is_edit_project' => ($app->config["default_edit_project_authorization"] === "edit"));
      $project_auth["users"] = $permissions;
      file_put_contents($app->config("base_dir").$data["project_url"]."/auth.json",json_encode($project_auth));
      symlink("/users/" .$data["added_user"], $app->config("base_dir").$data["project_url"]."/users/".$data["added_user"]);
      symlink($data["project_url"], $app->config("base_dir")."/users/" .$data["added_user"].$data["project_url"]);
    }
    $app->response->setStatus(STATUS_OK);
  }

  public function invitation_delete()
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$app->resource->getURL()."/info.json"), TRUE);
    $info["invitations"] = array();
    file_put_contents($app->config("base_dir").$app->resource->getURL()."/info.json",json_encode($info));
    $app->response->setStatus(STATUS_NO_CONTENT);
  }

  public function changePermissions($data)
  {
    global $app;
    if (!$this->link_exists())
    { 
      $app->response->setStatus(STATUS_NOT_FOUND);
      return;
    }
    $parts = explode('/', $this->getURL());
    $project_url = "/projects/".$parts[2];
    $username = $parts[count($parts) - 1];
    $project_auth = json_decode(file_get_contents($app->config("base_dir").$project_url."/auth.json"), TRUE);
    $permissions = $project_auth["users"];
    $user = $permissions[$username];
    $user["is_admin_project"] = (array_key_exists("is_admin_project", $data)) ? $data["is_admin_project"] : $user["is_admin_project"];
    $user["is_edit_project"] = (array_key_exists("is_edit_project", $data)) ? $data["is_edit_project"] : $user["is_edit_project"];
    $permissions[$username] = $user;
    $project_auth["users"] = $permissions;
    file_put_contents($app->config("base_dir").$project_url."/auth.json",json_encode($project_auth));
    $app->response->setStatus(STATUS_OK);
  }

  public function user_delete()
  {
    global $app;
    if (!$this->link_exists())
    { 
      $app->response->setStatus(STATUS_NOT_FOUND);
      return;
    }
    $parts = explode('/', $this->getURL());
    $project_url = "/projects/".$parts[2];
    $user_url = "/users/".$parts[count($parts) - 1];
    $project_auth = json_decode(file_get_contents($app->config("base_dir").$project_url."/auth.json"), TRUE);
    $permissions = $project_auth["users"];
    unset($permissions[$parts[count($parts) - 1]]);
    $project_auth["users"] = $permissions;
    unlink($app->config("base_dir").$this->getURL());
    unlink($app->config("base_dir").$user_url.$project_url);
    file_put_contents($app->config("base_dir").$project_url."/auth.json",json_encode($project_auth));
    $app->response->setStatus(STATUS_NO_CONTENT);
  }
}