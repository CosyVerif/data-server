<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Permission extends \Slim\Middleware
{

  public function call()
  {
    global $app;
    $url = $app->request->getResourceUri();
    $action = strtoupper($app->request->getMethod());
    if (($app->user["is_admin"] == USER_ADMIN) || 
        ($this->isItsResource($url)) ||
        ($this->permissionGranted($url, $action)))
      $this->next->call();
    else {
      $app->response->setStatus(STATUS_FORBIDDEN);
    }
  }

  private function permissionGranted($url, $action){
    global $app;
    $is_ok = false;
    if ($this->isProjectResource($url)){
      $tmpURL = $this->getProjectRealURL($url);
      if (file_exists("resources".$tmpURL)){
        $auth = json_decode(file_get_contents("resources".$tmpURL."/auth.json"), TRUE);
        $username = $app->user["login"];
        if(array_key_exists($username, $auth)){
          switch ($action) {
            case 'GET':
              $is_ok = (($auth[$username] == PROJECT_ADMIN) || 
                        ($auth[$username] == PROJECT_WRITE) ||
                        ($auth[$username] == PROJECT_READ));
              break;
            case 'PUT':
              $is_ok = (($auth[$username] == PROJECT_ADMIN) || 
                        (!($this->isUserResource($url)) && 
                          ($auth[$username] == PROJECT_WRITE)));
              break;
            case 'PATCH':
              $is_ok = (($auth[$username] == PROJECT_ADMIN) || 
                        (!($this->isUserResource($url)) && 
                          ($auth[$username] == PROJECT_WRITE)));
              break;
            case 'DELETE':
              $is_ok = (($auth[$username] == PROJECT_ADMIN) || 
                        (!($this->isUserResource($url)) && 
                          ($auth[$username] == PROJECT_WRITE)));
              break;
            default:
              $is_ok = true;
              break;
          }
        }
      }else {
        $is_ok = true;
      }
    } else {
      if (file_exists("resources".$url."/auth.json")){
        $auth = json_decode(file_get_contents("resources".$url."/auth.json"), TRUE);
        if($auth["is_public"] ==  RESOURCE_PUBLIC)
          $is_ok = true;
      }
    }
    return $is_ok;
  }

    /*

    if (trim($parts[1]) != "projects"){
      $app->redirect('/'.$app->server["main"].'/'.implode('/', array_slice($parts,3,count($parts))), STATUS_MOVED_PERMANENTLY);
    }*/
  
  private function isItsResource($url){
    $parts = explode('/', $url);
    if (('/users/'.trim($parts[2]) == '/users/'.$app->user["login"]) ||
        ('/users/'.trim($parts[4]) == '/users/'.$app->user["login"]))
      return true;
    else
      return false;
  }
  private function isProjectResource($url){
    $parts = explode('/', $url);
    return ((trim($parts[1]) == "projects") || (trim($parts[3]) == "projects"));
  }

  private function getProjectRealURL($url){
    $parts = explode('/', $url);
    if (trim($parts[1]) == "projects"){
      $url = '/'.implode('/', array_slice($parts,1,2));
    }else{
      $url = '/'.implode('/', array_slice($parts,3,2));
    }
    return $url;
  }

  private function isUserResource($url){
    $parts = explode('/', $url);
    if (trim($parts[1]) == "projects"){
      $url = implode('/', array_slice($parts,3,1));
    }else{
      $url = implode('/', array_slice($parts,5,1));
    }
    return ($url == "users");
  }
}
?>