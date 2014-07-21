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
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      $data = $app->resource->project_readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");

    // user router
    $app->get('/projects/:project(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->project_read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");
    $app->post('/projects/:project(/)', function() use($app)
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
      $app->resource->project_create($data);
    })->setName("project");
    $app->put('/projects/:project(/)', function() use($app)
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
      $app->resource->project_write($data);
    })->setName("project");
    $app->patch('/projects/:project(/)', function() use($app)
    {
                    
    });
    $app->delete('/projects/:project(/)', function() use ($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->delete_dir();
    })->setName("project");

    $app->get('/projects/:project/:others(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->project_readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("project");

        // project user router
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
    $app->patch('/projects/:id/users/:user(/)', function() use($app){
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
    mkdir($app->config["base_dir"].$this->getURL()."/users");
    $tmp = array();
    $tmp["name"] = "user list";
    file_put_contents($app->config["base_dir"].$this->getURL()."/users/info.json", json_encode($tmp));
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json",json_encode($data["info"]));
    file_put_contents($app->config["base_dir"].$this->getURL()."/auth.json", json_encode($data["auth"]));
    $app->response->setStatus(STATUS_CREATED);
  }

  public function project_write($data)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
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
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json",json_encode($info));
    file_put_contents($app->config["base_dir"].$this->getURL()."/auth.json", json_encode($auth));
    $app->response->setStatus(STATUS_OK);
  }

  public function project_read()
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

  public function project_readList()
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