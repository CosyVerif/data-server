<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Permission extends \Slim\Middleware
{

  public function call()
  {
    global $app;
    if (!($this->resourceExists($app->request->getResourceUri())))
      $this->next->call();
    else if (($this->permissionGranted()))
      $this->next->call();
    else
      $app->response->setStatus(STATUS_FORBIDDEN);
  }

  private function permissionGranted()
  {
    global $app;
    $action = strtoupper($app->request->getMethod());
    $url = $app->request->getResourceUri();
    $is_ok = false;
    switch ($app->user["user_type"]) {
      case USER_ADMIN: // Administrators
        $is_ok = (($action == 'DELETE') && ($this->isOneself($url))) ? false : true;
        break;
      case USER_DEFAULT: // No users authentified
        $is_ok = (($action == 'GET') && ($this->isPublicResource($url))) ? true : false;
        break;
      case USER_LIMIT: // Users authentified
        $is_ok = ($this->isGranted($url, $action)) ? true : false;
        break;
      default:
        $is_ok = false;
        break;
    }
    return $is_ok;
  }

  private function isGranted($url, $action)
  {
    $is_ok = false;
    switch ($action) {
      case 'GET':
        if (($this->isOneself($url)) || ($this->isItsResource($url)) ||
            ($this->isPublicResource($url)) || ($this->havePermission($url, $action)))
          $is_ok = true; 
        break;
      case 'PUT':
        if (($this->isOneself($url)) || ($this->isItsResource($url)) ||
            ($this->havePermission($url, $action)))
          $is_ok = true;
        break;
      case 'PATCH':
        if (($this->isOneself($url)) || ($this->isItsResource($url)) ||
            ($this->havePermission($url, $action)))
          $is_ok = true;
        break;
      case 'DELETE':
        if (($this->isItsResource($url)) || ($this->havePermission($url, $action)))
          $is_ok = true;
        break;   
      default:
        $is_ok = true;
        break;
    }
    return $is_ok;
  }

  private function havePermission($url, $action)
  {
    $permission = $this->getProjectPermissions($url);
    if (is_null($permission))
      return false;
    //Protect the privates resources of users
    $realURL = $this->getRealURL($url);
    if ($this->isProjectResource($realURL)){
      $realURL = $this->getRealURL($realURL);
    }
    $parts = explode('/', $realURL);
    if ((trim($parts[1]) == "users") && (count($parts) > 3))
      return false;
    //Verify permisssions
    $is_ok = false;
    if ($action == "GET"){
      $is_ok = (($permission == PROJECT_ADMIN) || 
                ($permission == PROJECT_WRITE) ||
                ($permission == PROJECT_READ)) ? true : false;
    } else if ($action == "PUT" || $action == "PATCH"){
      $projectUser = $this->isProjectUser($url);
      $is_ok = (($projectUser && $permission == PROJECT_ADMIN) ||
                (!($projectUser) && ($permission == PROJECT_ADMIN || $permission == PROJECT_WRITE))) ? true : false;
    } else if ($action == "DELETE"){
      $projectUser = $this->isProjectUser($url);
      $is_ok = (($projectUser && $permission == PROJECT_ADMIN) ||
                (!($projectUser) && ($permission == PROJECT_ADMIN || $permission == PROJECT_WRITE))) ? true : false;
    } else {
      $is_ok = true;
    }
    return $is_ok;
  }

/*
    if (trim($parts[1]) != "projects"){
      $app->redirect('/'.$app->server["main"].'/'.implode('/', array_slice($parts,3,count($parts))), STATUS_MOVED_PERMANENTLY);
    }
  */

  private function getProjectPermissions($url)
  {
    global $app;
    if (!($this->isProjectResource($url)))
      return null;
    $parts = explode('/', $url);
    if (trim($parts[1]) == "projects"){
      $url = '/'.implode('/', array_slice($parts, 1, 2));
    } else {
      $url = '/'.implode('/', array_slice($parts, 3, 2));
    }
    $auth = json_decode(file_get_contents("resources".$url."/auth.json"), TRUE);
    if(array_key_exists($app->user["login"], $auth))
      return $auth[$app->user["login"]];
    else
      return null;
  }

  private function isOneself($url)
  {
    global $app;
    $realURL = $this->getRealURL($url);
    if ($this->isProjectResource($realURL))
      $realURL = $this->getRealURL($realURL);
    $parts = explode('/', $realURL);
    if ((trim($parts[1]) == "users") && 
        (count($parts) == 3) && 
        (trim($parts[2]) == $app->user["login"]))
      return true;
    else
      return false;
  }

  private function isItsResource($url)
  {
    global $app;
    if ($this->isProjectResource($url))
      return false;
    $parts = explode('/', $url);
    if ((trim($parts[1]) == "users") && 
        (count($parts) > 3) && 
        (trim($parts[2]) == $app->user["login"]))
      return true;
    else
      return false;
  }

  private function isPublicResource($url)
  {
    $is_ok = false;
    if ($this->isProjectResource($url)){
      $is_ok = false;
    } else {
      $auth = json_decode(file_get_contents("resources".$this->getRealURL($url)."/auth.json"), TRUE);
      $is_ok = ($auth["is_public"] ==  RESOURCE_PUBLIC) ? true : false;
    } 
    return $is_ok;
  }

  private function isProjectResource($url)
  {
    $parts = explode('/', $url);
    return (((trim($parts[1]) == "projects") && (count($parts) > 2)) || 
            ((trim($parts[3]) == "projects") && (count($parts) > 4)));
  }

  private function isProjectUser($url)
  {
    $realURL = $this->getRealURL($url);
    if ($this->isProjectResource($realURL)){
      $realURL = $this->getRealURL($realURL);
    }
    $parts = explode('/', $realURL);
    if (trim($parts[1]) == "users")
      return true;
    else
      return false;
  }

  private function getRealURL($url)
  {
    $parts = explode('/', $url);
    if (((trim($parts[3]) == "users") || (trim($parts[3]) == "projects")) 
        && (count($parts) > 4)){
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
    }
    return $url;
  }

  private function resourceExists($url)
  { 
    $is_ok = false;
    $realURL = $this->getRealURL($url);
    if ($url == $realURL){
      if (file_exists("resources".$url))
        $is_ok = true;
    } else {
      $urlParts = explode('/', $url);
      $realURLParts = explode('/', $realURL);
      if ($urlParts[1] == "users"){
        if((file_exists("resources/users/".$urlParts[2]."/projects/".$realURLParts[2])) && 
           (file_exists("resources".$realURL)))
          $is_ok = true;
      } else if ($urlParts[1] == "projects"){
        if((file_exists("resources/projects/".$urlParts[2]."/users/".$realURLParts[2])) && 
           (file_exists("resources".$realURL)))
          $is_ok = true;
      }
    }
    return $is_ok;
  }
}
?>