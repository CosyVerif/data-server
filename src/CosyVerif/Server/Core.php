<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Core  extends \Slim\Middleware
{
  public function call()
  {
    global $app;
    // list router
    $app->get('/', function() use($app)
    {
      $data = file_get_contents("website/index.html");
      if ($data == FALSE)
      {
        $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      }
      else
      {
        $app->response->setStatus(STATUS_OK);
        $app->response->headers->set('Content-Type','text/html');
        $app->response->setBody($data);
      }
    })->setName("website");
    $app->get('/website/:type/:name', function($type, $name) use($app)
    {
      $data = file_get_contents("website/".$type."/".$name);
      if ($data == FALSE)
      {
        $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      }
      else
      {
        $app->response->setStatus(STATUS_OK);
        $app->response->setBody($data);
        if ($type == "html")
        {
          $app->response->headers->set('Content-Type','text/html');
        }
        else if ($type == "css")
        {
          $app->response->headers->set('Content-Type','text/css');
        }
        else if ($type == "img")
        {
          $app->response->headers->set('Content-Type','image/jpeg');
        }
      }
    })->setName("website");
    $app->get('/(users|projects)(/)', function() use($app)
    {
      $data = $this->readList($app->request->getResourceUri());
      if (!is_null($data)){$app->response->setBody($data);}
    })->setName("list");

    $type = (preg_match('#^/users#', $app->request->getResourceUri())) ? "user" : "project";

    // user router
    $app->get('/(users|projects)/:id(/)', function() use($app)
    {
      $data = $this->user("get-user", $app->request->getResourceUri(), $data = NULL);
      if (!is_null($data)){$app->response->setBody($data);}
    })->setName($type);
    $app->put('/(users|projects)/:id(/)', function() use($app)
    {
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $this->user("put-user", $app->request->getResourceUri(), $data);
    })->setName($type);
    $app->patch('/(users|projects)/:id(/)', function() use($app)
    {
                    
    })->setName($type);
    $app->delete('/(users|projects)/:id(/)', function() use ($app)
    {
      $this->user("delete-user", $app->request->getResourceUri(), $data = NULL);
    })->setName($type);
    // resource list router
    $app->get('/(users|projects)/:id/:others(/)', function($id, $others) use($app)
    {
      $data = $this->readList($app->request->getResourceUri());
      if (!is_null($data)){$app->response->setBody($data);}
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
      $data = $this->readList($app->request->getResourceUri());
      if (!is_null($data)){$app->response->setBody($data);}
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
      $app->response->setBody($this->model("get-model", $app->request->getResourceUri(), $data = NULL));
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $this->model("put-model", $app->request->getResourceUri(), $data);
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
      $patch_data = "local p = function (model) model.x = 1 end";
      $app->response->setBody($patch_data);
      $app->response->setStatus(STATUS_OK);
    })->setName($type."-resource");   
    $app->get('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
       echo " :patches : ".$app->request->params('from')." - ".$app->request->params('to');
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

      $app->response->setBody($this->model("edit-mode", $app->request->getResourceUri(), $data = NULL));

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
    $app->get('/users/:id/projects/:project+', function() use($app){})->setName("user-project");
    $app->put('/users/:id/projects/:project+', function() use($app){})->setName("user-project");
    $app->patch('/users/:id/projects/:project+', function() use($app){})->setName("user-project");
    $app->delete('/users/:id/projects/:project+', function() use($app){})->setName("user-project");

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

  private function readList($url)
  {
      $resource = StreamBase::readList($url); 
      if (is_array($resource))
        return json_encode($resource);
      else
        return NULL;
  }
  private function model($action, $url, $data)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
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
        return json_encode(array('url' => $luaURL, 'token' => $userToken));
        break;

      case 'get-model':
        $data = StreamModel::read($url);
        if (is_array($data))
          return json_encode($data);
        break;

      case 'put-model':
        return StreamModel::write($url, $data);
        break;

      default:
        # code...
        break;
    }
  }

  private function user($action, $url, $data)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    switch ($action) {
      case 'get-user':
        $data = StreamUserProject::read($url);
        if (is_array($data))
          return json_encode($data);
        else
          return NULL;
        break;
      case 'put-user':
        return StreamUserProject::write($url, $data);
        break;

      case 'delete-user':
        return StreamUserProject::delete($url);
        break;

      default:
        # code...
        break;
    }
  }
}