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
      $this->app->hook('slim.before.dispatch',  function() use ($app, $userID, $method){
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
    if (!file_exists("resources".$app->request->getResourceUri()) && $method != "PUT")
    { // Resource not exists
      $app->halt(STATUS_NOT_FOUND);
    } 
    else if ($routeName == "list")
    {
      return true;
    }
    else if (isset($params["userID"]) && $params["userID"] == "root")
    { // Requested resource is root resource :  Root does not have a resources
      return false;
    }
    else if ($userID == "root" && $routeName == "user")
    {
      return true;
    } 
    else if ($routeName == "user-project" || $routeName == "project-user" ||  
             $routeName == "project-user-resource" )
    { 
      if ($routeName == "project-user" && ($method == "PUT" || $method == "DELETE"))
      {
        $auth = json_decode(file_get_contents("resources/projects/".$params["projectID"]."/auth.json"), TRUE);
        $users = $auth["users"];
        $can_participate = (!is_null($userID)) ? array_key_exists($userID, $users) : false;
        $permissions = ($can_participate) ? $users[$userID] : array();
        return ($can_participate && (($method == "PUT" && $permissions["change_project"]) ||
                ($method == "DELETE" && ($permissions["change_project"] || $params["projectUserID"] == $userID))));
      }
      else 
      {
        $parts = explode('/', $app->request->getResourceUri());
        $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
        $app->redirect('/'.$app->server["main"].$url, STATUS_MOVED_TEMPORARILY);
      } 
    }
    else if ($routeName == "user" || $routeName == "user-resource" || $routeName == "user-resourceList")
    {
      if ($method == "GET")
      {
        return ($params["userID"] == $userID) ? true : $this->isPublicResource("/users/".$params["userID"]);
      }
      else if ($params["userID"] == $userID && ($routeName == "user" || $routeName == "user-resource"))
      {
        return true;
      } 
      else if (!is_null($userID) && $params["userID"] != $userID &&  $routeName == "user")
      {
        $auth = json_decode(file_get_contents("resources/users/".$userID."/auth.json"), TRUE);
        $permissions = $auth["permissions"];
        return (($method == "DELETE" && $permissions["user_delete"]) ||
                ($method == "PUT" && ((!file_exists("/users/".$params["userID"]) && 
                      $permissions["user_create"]) || (file_exists("/users/".$params["userID"]) && 
                      $permissions["user_modify"]))) ||
                ($method == "PATCH" && $permissions["user-modify"]));
      }
      else
        return false;
    }
    else if ($routeName == "project" || $routeName == "project-resource" || $routeName == "project-resourceList")
    {
      $auth = json_decode(file_get_contents("resources/projects/".$params["projectID"]."/auth.json"), TRUE);
      $users = $auth["users"];
      $can_participate = (!is_null($userID)) ? array_key_exists($userID, $users) : false;
      if ($method == "GET")
      {
        return ($can_participate || $this->isPublicResource("/projects/".$params["projectID"])) ? true : false;
      } 
      else if ($routeName == "project" || $routeName == "project-resource")
      {
        $permissions = ($can_participate) ? $users[$userID] : array();
        return ($can_participate && (($routeName == "project" && $permissions["change_project"]) || 
                ($routeName == "project-resource" && $permissions["change_resource"]))) ? true : false;
      } 
      else
        return false;
    }
    return false;
  }

  private function isPublicResource($url)
  {
    $auth = json_decode(file_get_contents("resources".$url."/auth.json"), TRUE);
    return ($auth["can_public"] ==  IS_PUBLIC);
  }
}
?>