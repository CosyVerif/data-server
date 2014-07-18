<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Core  extends \Slim\Middleware
{
  public function call()
  {
    global $app;
    // list router
    $app->get('/(webclient|html|js|lua|css|img)(/:name+)', function() use($app)
    {
      $url = $app->request->getResourceUri();
      if ($url == "/")
        $this->website("webclient/index.html");
      else if (substr($url, 1,9) == "webclient")
        $this->website(substr($url, 1));
      else
        $this->website("webclient".$url);
    })->setName("webclient");
    $app->get('/(users|projects)(/)', function() use($app)
    { 
      $this->readList(); 
    })->setName("list");

    $type = (preg_match('#^/users#', $app->request->getResourceUri())) ? "user" : "project";

    // user router
    $app->get('/(users|projects)/:id(/)', function() use($app)
    {
      $this->userORproject("get-user");
    })->setName($type);
    $app->put('/(users|projects)/:id(/)', function() use($app)
    {
      $this->userORproject("put-user");
    })->setName($type);
    $app->patch('/(users|projects)/:id(/)', function() use($app)
    {
                    
    })->setName($type);
    $app->delete('/(users|projects)/:id(/)', function() use ($app)
    {
      $this->userORproject("delete-user");
    })->setName($type);
    // resource list router
    $app->get('/(users|projects)/:id/:others(/)', function($id, $others) use($app)
    { 
      $this->readList(); 
    })->setName($type."-resourceList"); 
    // formalisms router
    $app->get('/(users|projects)/:id/formalisms/:formalism(/)', function() use($app)
    {
      echo " :formalism: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/formalisms/:formalism(/)', function() use($app)
    {
      echo " :formalism: ";
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/formalisms/:formalism(/)', function() use($app)
    {
      echo " :formalism: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/formalisms/:formalism(/)', function() use($app)
    {
      echo " :formalism: ";
    })->setName($type."-resource"); 
      // converters router
    $app->get('/(users|projects)/:id/formalisms/:formalism/converters(/)', function() use($app)
    {
      $this->readList();
    })->setName($type."-resourceList"); 
    $app->get('/(users|projects)/:id/formalisms/:formalism/converters/:converter(/)', function() use($app)
    {
      echo " :converter: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/formalisms/:formalism/converters/:converter(/)', function() use($app)
    {
            echo " :converter: ";
    })->setName($type."-resource"); 
    $app->patch('/(users|projects)/:id/formalisms/:formalism/converters/:converter(/)', function() use($app)
    {
            echo " :converter: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/formalisms/:formalism/converters/:converter(/)', function() use($app)
    {
            echo " :converter: ";
    })->setName($type."-resource"); 
    // models router
    $app->get('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      $this->model("get-model");
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      $this->model("put-model");
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      $app->response->setStatus(STATUS_CREATED);
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
            echo " :model: ";
    })->setName($type."-resource");
    $app->get('/(users|projects)/:id/models/:model/patches/:patch(/)', function() use($app)
    {
      $this->model("get-patch");
    })->setName($type."-resource");   
    $app->get('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
      $this->model("get-patches");
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
       echo " :patches : ".$app->request->params('from')." - ".$app->request->params('to');
    })->setName($type."-resource");    
    $app->delete('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
      echo " :patches : ".$app->request->params('from')." - ".$app->request->params('to');
    })->setName($type."-resource");
    $app->get('/(users|projects)/:id/models/:model/editor(/)', function() use($app)
    {
      /*
      $data = json_decode(file_get_contents($app->config["base_dir"]."/users/enter_edit_mode_user/models/model_1/editor/info.json"), TRUE);
        
      if (count($data) == 0) 
      {
        $info = array('url' => "127.0.0.1", 'port' => 300);
        file_put_contents($app->config["base_dir"]."/users/enter_edit_mode_user/models/model_1/editor/info.json", json_encode($info));
      }
      $data = json_decode(file_get_contents($app->config["base_dir"]."/users/enter_edit_mode_user/models/model_1/editor/info.json"), TRUE);
      $app->response->setBody(json_encode($data));
      $app->response->headers->set('Content-Type','application/json');*/

      $this->model("edit-mode");

    })->setName($type."-resource"); 

    // scenarios router
    $app->get('/(users|projects)/:id/scenarios/:scenario(/)', function() use($app)
    {
            echo " :scenario: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/scenarios/:scenario(/)', function() use($app)
    {
            echo " :scenario: ";
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/scenarios/:scenario/)', function() use($app)
    {
            echo " :scenario: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/scenarios/:scenario(/)', function() use($app)
    {
            echo " :scenario: ";
    })->setName($type."-resource"); 

    // services router
    $app->get('/(users|projects)/:id/services/:service(/)', function() use($app)
    {
            echo " :service: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/services/:service(/)', function() use($app)
    {
            echo " :service: ";
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/services/:service/)', function() use($app)
    {
            echo " :service: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/services/:service(/)', function() use($app)
    {
            echo " :service: ";
    })->setName($type."-resource"); 

    // execution router
    $app->get('/(users|projects)/:id/executions/:execution(/)', function() use($app)
    {
            echo " :execution: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/executions/:execution(/)', function() use($app)
    {
            echo " :execution: ";
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/executions/:execution/)', function() use($app)
    {
          echo " :execution: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/executions/:execution(/)', function() use($app)
    {
            echo " :execution: ";
    })->setName($type."-resource"); 

    // user project router
    $app->get('/users/:id/projects/:project(/:name+)', function() use($app){})->setName("user-project");
    $app->put('/users/:id/projects/:project(/:name+)', function() use($app){})->setName("user-project");
    $app->patch('/users/:id/projects/:project(/:name+)', function() use($app){})->setName("user-project");
    $app->delete('/users/:id/projects/:project(/:name+)', function() use($app){})->setName("user-project");

    // project user router
    $app->get('/projects/:id/users/:user(/)', function() use($app){})->setName("project-user"); 
    $app->put('/projects/:id/users/:user(/)', function() use($app){})->setName("project-user"); 
    $app->patch('/projects/:id/users/:user(/)', function() use($app){})->setName("project-user"); 
    $app->delete('/projects/:id/users/:user(/)', function() use($app){})->setName("project-user"); 
    $app->get('/projects/:id/users/:user/:other+', function() use($app){})->setName("project-user-resource"); 
    $app->put('/projects/:id/users/:user/:other+', function() use($app){})->setName("project-user-resource"); 
    $app->patch('/projects/:id/users/:user/:other+', function() use($app){})->setName("project-user-resource"); 
    $app->delete('/projects/:id/users/:user/:other+', function() use($app){})->setName("project-user-resource"); 

    $this->next->call();
  }

  private function readList()
  {
    global $app;
    $data = StreamBase::readList($app->request->getResourceUri()); 
    if (is_array($data))
      return $app->response->setBody(json_encode($data));
  }
  private function model($action)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $url = $app->request->getResourceUri();
    switch ($action) {
      case 'edit-mode':
        $luaURL = StreamModel::getUrl($url);
        $userToken = StreamModel::generateToken();
        $can_ok = true; // verify if server lua exist that port
        if ($can_ok)
        {
          // formToken {user_token, user_read, user_write} 
          $serverToken = StreamModel::getToken($url);
          // send to lua server 
        }
        else
        {
          //create server tocken and choice port and create server lua
          $serverToken = StreamModel::generateToken();
          $luaURL = "ws://localhost:300"; // Choice port
          //create lua server
          StreamModel::saveUrl($url, $luaURL);
          StreamModel::saveToken($url, $serverToken);
        }
        $app->response->headers->set('Content-Type','application/json');
        $app->response->setStatus(STATUS_OK);
        $app->response->setBody(json_encode(array('url' => $luaURL, 'token' => $userToken)));
        break;

      case 'get-model':
        $data = StreamModel::read($url);
        if (is_array($data))
          $app->response->setBody(json_encode($data));
        break;

      case 'put-model':
        $data = json_decode($app->request->getBody(), TRUE);
        if (!is_array($data))
        {
          $app->halt(STATUS_UNPROCESSABLE_ENTITY);
        }
        StreamModel::write($url, $data);
        $app->response->setBody("{}");

        break;

      case 'get-patch':
        $data = StreamModel::readPatch($url);
        if (is_array($data))
          $app->response->setBody(json_encode($data));
        break;

      case 'get-patches':
        //$from = $app->request->getParam("from");
        //$to = $app->request->getParam("to");
        $data = StreamModel::readPatchList($url, $from = NULL, $to = NULL);
        if (is_array($data))
          $app->response->setBody(json_encode($data));
        break;

      default:
        # code...
        break;
    }
  }

  private function userORproject($action)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $url = $app->request->getResourceUri();
    switch ($action) {
      case 'get-user':
        $data = StreamUserProject::read($url);
        if (is_array($data))
          $app->response->setBody(json_encode($data));
        break;
      case 'put-user':
        $data = json_decode($app->request->getBody(), TRUE);
        if (!is_array($data))
        {
          $app->halt(STATUS_UNPROCESSABLE_ENTITY);
        }
        StreamUserProject::write($url, $data);
        break;

      case 'delete-user':
        StreamUserProject::delete($url);
        break;

      default:
        # code...
        break;
    }
  }

  private function website($url)
  {
    global $app;
    $data = file_get_contents($url);
    if ($data == FALSE)
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    }
    else
    {
      $app->response->setStatus(STATUS_OK);
      $app->response->setBody($data);
      $pathInfo = pathinfo($url);
      $extension = $pathInfo['extension'];
      if ($extension == "html")
      {
        $app->response->headers->set('Content-Type','text/html');
      }
      else if ($extension == "css")
      {
        $app->response->headers->set('Content-Type','text/css');
      }
      else if ($extension == "js")
      {
        $app->response->headers->set('Content-Type','application/javascript');
      }
      else if ($extension == "jpg")
      {
        $app->response->headers->set('Content-Type','image/jpeg');
      } 
      else if ($extension == "png")
      {
        $app->response->headers->set('Content-Type','image/png');
      } 
      
    }
  }
}