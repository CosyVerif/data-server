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
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    // user router
    $app->get('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");
    $app->put('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->write($data);
    })->setName("user");
    $app->patch('/users/:user(/)', function() use($app)
    {
                    
    });
    $app->delete('/users/:user(/)', function() use ($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->delete();
    })->setName("user");

    $app->get('/users/:user/:others(/)', function() use($app)
    {
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    // user project router
    $app->get('/users/:id/projects/:project(/|/:name+)', function() use($app){
      $parts = explode('/', $app->request->getResourceUri());
      $url = '/'.implode('/', array_slice($parts, 3, count($parts)));
      $app->redirect('/'.$app->config["main"].$url, STATUS_MOVED_TEMPORARILY);
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
    $app->delete('/users/:id/projects/:project(/|/:name+)', function() use($app){
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

  public function read()
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    //Verify existing resource
    if (!file_exists($app->config["base_dir"].$this->getURL()))
    {
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    else if ($this->isEmptyDir($app->config["base_dir"].$this->getURL()))
    {
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $resource = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    if (!is_array($resource))
    {
      return null;
    }    
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $resource;
  }

  public function write($data)
  {
    global $app;
    //Write ressource
    $is_ok = false;
    $info = "";
    $auth = "";
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (file_exists($app->config["base_dir"].$this->getURL()) &&   
        !$this->isEmptyDir($app->config["base_dir"].$this->getURL()))
    {
      // Resource not found
      $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
      //Update resource
      foreach ($data["info"] as $field => $value)
      {
        $info[$field] = $value;
      }
      $auth = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/auth.json"), TRUE);
      //Update resource
      foreach ($data["auth"] as $field => $value)
      {
        $info[$field] = $value;
      }
      $app->response->setStatus(STATUS_OK);
    } 
    else 
    {
      if(!file_exists($app->config["base_dir"].$this->getURL()))
      {
        mkdir($app->config["base_dir"].$this->getURL());
      }
      $tmp = array();
      mkdir($app->config["base_dir"].$this->getURL()."/formalisms");
      $tmp["name"] = "Formalism list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/formalisms/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/models");
      $tmp["name"] = "Model list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/models/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/scenarios");
      $tmp["name"] = "scenarios list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/scenarios/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/services");
      $tmp["name"] = "Service list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/services/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/executions");
      $tmp["name"] = "Execution list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/executions/info.json", json_encode($tmp)); 
      mkdir($app->config["base_dir"].$this->getURL()."/projects");
      $tmp = array();
      $tmp["name"] = "Project list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/projects/info.json", json_encode($tmp));
      $info = $data["info"];
      $auth = $data["auth"];
      $auth["password"] = password_hash($auth["login"].$auth["password"], PASSWORD_DEFAULT);
      $app->response->setStatus(STATUS_CREATED);
    }
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json",json_encode($info));
    file_put_contents($app->config["base_dir"].$this->getURL()."/auth.json", json_encode($auth));
    return $is_ok = true;
  }
}