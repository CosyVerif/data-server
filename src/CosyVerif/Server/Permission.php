<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Permission extends \Slim\Middleware
{
  public function call()
  {
    global $app;
    $userID = $app->user["login"];
    $method = strtoupper($app->request->getMethod());
    $this->app->hook('slim.before.dispatch',  function() use ($app, $userID, $method)
    {
      if (!($this->permissionGranted($userID, $method)))
        $app->halt(STATUS_FORBIDDEN);
    });
    $this->next->call();      
  }

  private function permissionGranted($userID, $method)
  {
    global $app;
    $params = $this->app->router()->getCurrentRoute()->getParams();
    $pattern = $this->app->router()->getCurrentRoute()->getPattern();
    $routeName = $this->app->router()->getCurrentRoute()->getName();
    if (!file_exists($app->config["base_dir"].$app->request->getResourceUri()) && $method != "PUT")
    { // Resource not exists
      $app->halt(STATUS_NOT_FOUND);
    } 
    else if ($routeName == "list")
    {
      return ($method == "GET") ? true : false;
    }
    else if (($routeName == "user" || 
             $routeName == "user-resource" || 
             $routeName == "user-resourceList" || 
             $routeName == "user-project") &&
             $params["id"] == $app->config["user_root"])
    { // Requested resource is root resource :  Root does not have a resources
      return false;
    }
    else if ($userID == $app->config["user_root"] && $routeName == "user")
    {
      return true;
    } 
    else if ($routeName == "user-project" || 
             $routeName == "project-user" ||  
             $routeName == "project-user-resource")
    { 
      if ($routeName == "project-user" && 
          ($method == "PUT" || $method == "DELETE"))
      {
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$params["id"]."/auth.json"), TRUE);
        $users = $auth["users"];
        $can_participate = (!is_null($userID)) ? array_key_exists($userID, $users) : false;
        $permissions = ($can_participate) ? $users[$userID] : array();
        return ($can_participate && ($permissions["admin_project"] || 
                ($method == "DELETE" && $params["user"] == $userID)));
      }
      else 
      {
        $parts = explode('/', $app->request->getResourceUri());
        $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
        $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
      } 
    }
    else if ($routeName == "user" || $routeName == "user-resource" || $routeName == "user-resourceList")
    {
      if ($method == "GET")
      {
        return ($params["id"] == $userID) ? true : $this->isPublicResource("/users/".$params["id"]);
      }
      else if ($params["id"] == $userID && ($routeName == "user" || $routeName == "user-resource"))
      {
        return true;
      } 
      else if (!is_null($userID) && $params["id"] != $userID &&  $routeName == "user")
      {
        $auth = json_decode(file_get_contents($app->config["base_dir"]."/users/".$userID."/auth.json"), TRUE);
        return ($auth["admin_user"]);
      }
      else
        return false;
    }
    else if ($routeName == "project" || $routeName == "project-resource" || $routeName == "project-resourceList")
    {
      $auth = json_decode(file_get_contents($app->config["base_dir"]."/projects/".$params["id"]."/auth.json"), TRUE);
      $users = $auth["users"];
      $can_participate = (!is_null($userID)) ? array_key_exists($userID, $users) : false;
      if ($method == "GET")
      {
        return ($can_participate || $this->isPublicResource("/projects/".$params["id"]));
      } 
      else if ($routeName == "project" || $routeName == "project-resource")
      {
        $permissions = ($can_participate) ? $users[$userID] : array();
        return ($can_participate && 
                (($routeName == "project" && $permissions["admin_project"]) || 
                 ($routeName == "project-resource" && $permissions["edit_project"])));
      } 
      else
        return false;
    }
    return false;
  }

  private function isPublicResource($url)
  {
    global $app;
    $auth = json_decode(file_get_contents($app->config["base_dir"].$url."/auth.json"), TRUE);
    return ($auth["can_public"] ==  IS_PUBLIC);
  }
}
?>