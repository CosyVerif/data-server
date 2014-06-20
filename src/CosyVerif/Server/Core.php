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
      $data = $this->get($app->request->getResourceUri(), true);
      if (!is_null($data)){$app->response->setBody($data);}
    })->setName("list");
    $app->get('/(users|projects)(/)', function() use($app)
    {
      $data = $this->get($app->request->getResourceUri(), true);
      if (!is_null($data)){$app->response->setBody($data);}
    })->setName("list");

    $type = (preg_match('#^/users#', $app->request->getResourceUri())) ? "user" : "project";

    // user router
    $app->get('/(users|projects)/:id(/)', function() use($app)
    {
      $data = $this->get($app->request->getResourceUri(), true);
      if (!is_null($data)){$app->response->setBody($data);}
    })->setName($type);
    $app->put('/(users|projects)/:id(/)', function() use($app)
    {
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $this->put($app->request->getResourceUri(), $data);
    })->setName($type);
    $app->patch('/(users|projects)/:id(/)', function() use($app)
    {
                    
    })->setName($type);
    $app->delete('/(users|projects)/:id(/)', function() use ($app)
    {
      $this->delete($app->request->getResourceUri());
    })->setName($type);
    // resource list router
    $app->get('/(users|projects)/:id/:others(/)', function($id, $others) use($app)
    {
      $data = $this->get($app->request->getResourceUri(), true);
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
      $data = $this->get($app->request->getResourceUri(), true);
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
            echo " :model: ";
    })->setName($type."-resource"); 
    $app->put('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
            echo " :model: ";
    })->setName($type."-resource");   
    $app->patch('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
            echo " :model: ";
    })->setName($type."-resource"); 
    $app->delete('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
            echo " :model: ";
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

  private function get($url,$isList)
  { 
    if ($isList)
    {
      $resource = StreamJson::readResourceList($url); 
    } 
    else 
    {
      $resource = StreamJson::readResource($url); 
    }
    if (is_array($resource))
    {
      $resource = json_encode($resource);
    }
    return $resource;
  }

  private function put($url, $data)
  {
    $is_ok = StreamJson::write($url, $data);
    return $is_ok;
  }

  private function delete($url)
  {
    $is_ok = StreamJson::delete($url);
    return $is_ok;
  }
}