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
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      $data = $app->resource->user_readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");

    // user router
    $app->get('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->user_read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("user");
    $app->post('/users/:user(/)', function() use($app)
    {
      if ($app->resource->exists() && !$app->resource->deleted())
        $app->halt(STATUS_CONFLICT);
      if (!$app->resource->canCreate($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->user_create($data);
    })->setName("user");
    $app->put('/users/:user(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->user_write($data);
    })->setName("user");
    $app->patch('/users/:user(/)', function() use($app)
    {
                    
    });
    $app->delete('/users/:user(/)', function() use ($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->delete_dir();
    })->setName("user");

    $app->get('/users/:user/:others(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->user_readList(); 
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

  public function user_create($data)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if(!$this->exists())
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
    $auth = $data["auth"];
    $auth["password"] = password_hash($auth["login"].$auth["password"], PASSWORD_DEFAULT);
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json",json_encode($data["info"]));
    file_put_contents($app->config["base_dir"].$this->getURL()."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_CREATED);
  }

  public function user_write($data)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    foreach ($data["info"] as $field => $value)
    {
      $info[$field] = $value;
    }
    $auth = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/auth.json"), TRUE);
    foreach ($data["auth"] as $field => $value)
    {
      $info[$field] = $value;
    }
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json",json_encode($info));
    file_put_contents($app->config["base_dir"].$this->getURL()."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_OK);
  }

  public function user_read()
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $resource = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    if (!is_array($resource))
    {
      return null;
    }    
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $resource;
  }

  public function user_readList()
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    $data = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config["base_dir"].$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue;
      else if (!$this->isEmptyDir($file))
      { 
        $tmp = json_decode(file_get_contents($file."/info.json"), TRUE);
        $resourceList[] = array('href' => $this->getURL()."/".basename($file), 'name' => $tmp["name"]);
      }
    }
    $data["resource_list"] = $resourceList;
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }
}