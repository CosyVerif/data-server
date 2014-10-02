<?php

namespace CosyVerif\Server\Routing;

class HeaderMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new HeaderMiddleware());
  }
  public function call()
  {
    global $app;

    $url = $app->request->getResourceUri();
    $this->app->hook('slim.before.dispatch',  function() use ($app, $url)
    {
      $routeName = $app->router()->getCurrentRoute()->getName();
      if ($routeName == "header")
      {
        $app->resource = HeaderResource::newResource($url);
      }

    });

    $app->get('/(users|projects)/:id/:resources(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("header"); 
    $app->get('/(users|projects)/:id/:resources/:resource(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("header"); 
    $app->post('/(users|projects)/:id/:resources/:resource(/)', function() use($app)
    {
      if (($app->resource->file_exists() && !$app->resource->file_deleted()) || !$app->resource->url_isValid())
        $app->halt(STATUS_CONFLICT);
      if (!$app->resource->canCreate($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->create($data);
      $app->response->setBody("{}");
    })->setName("header"); 
    $app->put('/(users|projects)/:id/:resources/:resource(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->write($data);
      $app->response->setBody("{}");
    })->setName("header");   
    $app->patch('/(users|projects)/:id/:resources/:resource(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if ($app->resource->file_deleted())
        $app->halt(STATUS_GONE);
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->patch_create($data);
      $app->response->setBody("{}");
    })->setName("header");
    $app->delete('/(users|projects)/:id/:resources/:resource(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->delete_dir();
    })->setName("header");
    $app->get('/(users|projects)/:id/:resources/:resource/patches/:patch(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->patch_read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("header");   
    $app->get('/(users|projects)/:id/:resources/:resource/patches(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $from = $app->request->getParam("from");
      $to = $app->request->getParam("to");
      $data = $app->resource->patch_readList($from, $to);
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("header");      
    $app->delete('/(users|projects)/:id/:resources/:resource/:path(/)', function() use($app)
    {
      if (!$app->resource->file_exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canDelete($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $app->resource->delete_file();
    })->setName("header");

    $this->next->call();
  }
}

class HeaderResource extends BaseResource
{
  public static function newResource($url)
  {
    return new HeaderResource($url);
  }

  public function create($data)
  {
    global $app;
    if (!isset($data["name"]) || !isset($data["description"]))
    {
      $app->response->setStatus(STATUS_BAD_REQUEST);
      return;
    }
    $info = json_encode(array('name' => $data["name"], 'description' => $data["description"]));
    if(!$this->file_exists())
    {
      mkdir($app->config("base_dir").$this->getURL());
    }
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json", $info);
    file_put_contents($app->config("base_dir").$this->getURL()."/data.cosy", (isset($data["data"])) ? $data["data"] : "");
    $tmp = array();
    mkdir($app->config("base_dir").$this->getURL()."/patches");
    $tmp["name"] = "Patch list";
    file_put_contents($app->config("base_dir").$this->getURL()."/patches/info.json", json_encode($tmp));
    $app->response->setStatus(STATUS_CREATED);
  }

  public function write($data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $info["name"] = (array_key_exists("name", $data))? $data["name"] : $info["name"];
    $info["description"] = (array_key_exists("description", $data))? $data["description"] : $info["description"];
    file_put_contents($app->config("base_dir").$this->getURL()."/info.json", json_encode($info));
    if (array_key_exists("data", $data))
      file_put_contents($app->config("base_dir").$this->getURL()."/data.cosy", $data["data"]);
    $app->response->setStatus(STATUS_OK); 
  }

  public function read()
  {
    global $app;
    $info = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $cosy = file_get_contents($app->config("base_dir").$this->getURL()."/data.cosy");
    $data = array('name' => $info["name"], 'description' => $info["description"]);
    $data["data"] = $cosy;
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $data["is_copy"] = (is_null($app->user))? false : true;
    $data["is_move"] = $data["is_edit"];
    $parts = explode('/', $this->getURL());
    $project_name = "";
    if ($parts[1] == "users")
    {
      $data["project_url"] = "/users/".$parts[2];
      $data["project_type"] = "User";
      $object = UserResource::newResource($data["project_url"])->user_read();
    }
    else 
    {
      $data["project_url"] = "/projects/".$parts[2];
      $data["project_type"] = "Project";
      $object = ProjectResource::newResource($data["project_url"])->project_read();
    }
    $data["project_name"] = $object["name"];
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function readList()
  {
    global $app;
    $data = json_decode(file_get_contents($app->config("base_dir").$this->getURL()."/info.json"), TRUE);
    $resourceList = array();
    foreach (glob($app->config("base_dir").$this->getURL().'/*', GLOB_NOESCAPE) as $file) 
    {
      if (!is_dir($file))
        continue; 
      try
      {
        $tmp = HeaderResource::newResource($this->getURL()."/".basename($file))->read();
        $tmp['href'] = $this->getURL()."/".basename($file);
        $resourceList[] = $this->addInformations($tmp); 
      }
      catch (\Exception $e){ continue; }
    }
    $data["resource_list"] = $resourceList;
    $data["is_create"] = $this->canCreate($app->user);
    $data["is_edit"] = $this->canWrite($app->user);
    $data["is_delete"] = $this->canDelete($app->user);
    $data["is_read"] = $this->canRead($app->user);
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;
  }

  public function patch_create($data)
  {
    global $app;
    $cosy_data = file_get_contents($app->config("base_dir").$this->getURL()."/data.cosy");
    $cosy_data = $cosy_data."\n\n".$data["data"];
    file_put_contents($app->config("base_dir").$this->getURL()."/data.cosy", $cosy_data);
    $app->response->setStatus(STATUS_OK); 
  }

  public function patch_read()
  {
    global $app;
    $path = file_get_contents($app->config("base_dir").$this->getURL());
    if ($path == FALSE)
    {
      return null;
    } 
    $pathInfo = pathinfo($app->config("base_dir").$this->getURL(), PATHINFO_FILENAME);
    $data = array ('name' => $pathInfo["filename"], 'data' => $path);
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;    
  }

  public function patch_readList($from, $to)
  {
    global $app;
    $data = array();
    foreach(glob($app->config("base_dir").$this->getURL(). '/*') as $file) 
    {
      if(is_dir($file) || basename($file) == "info.json")
        continue;
      $pathInfo = pathinfo($file);
      $filename = $pathInfo["filename"];
      if (!is_null($from) && strcmp($filename, $from) < 0)
        continue;
      else if (!is_null($to) && strcmp($filename, $to) > 0)
        continue;
      $data[] = array('name' => $filename, 'data' => file_get_contents($file));
    }
    $app->response->headers->set('Content-Type','application/json');
    $app->response->setStatus(STATUS_OK);
    return $data;    
  }
}
