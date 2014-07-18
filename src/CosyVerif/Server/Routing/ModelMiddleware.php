<?php
namespace CosyVerif\Server\Routing;

class ModelMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new ModelMiddleware());
  }
  public function call()
  {
    global $app;

    $url = $app->request->getResourceUri();
    $this->app->hook('slim.before.dispatch',  function() use ($app, $url)
    {
      $routeName = $app->router()->getCurrentRoute()->getName();
      if ($routeName == "model")
      {
        $app->resource = ModelResource::newResource($url);
      }

    });

    $app->get('/(users|projects)/:id/models(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->readList(); 
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("model"); 
    $app->get('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->read();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("model"); 
    $app->put('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data))
      {
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $app->resource->write($data);
      $app->response->setBody("{}");
    })->setName("model");   
    $app->patch('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
      $app->response->setStatus(STATUS_CREATED);
    })->setName("model"); 
    $app->delete('/(users|projects)/:id/models/:model(/)', function() use($app)
    {
            echo " :model: ";
    })->setName("model");
    $app->get('/(users|projects)/:id/models/:model/patches/:patch(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $data = $app->resource->readPatch();
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("model");   
    $app->get('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canRead($app->user))
        $app->halt(STATUS_FORBIDDEN);
      //$from = $app->request->getParam("from");
      //$to = $app->request->getParam("to");
      $data = $app->resource->readPatchList($from = NULL, $to = NULL);
      if (is_array($data))
        $app->response->setBody(json_encode($data));
    })->setName("model"); 
    $app->put('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
       echo " :patches : ".$app->request->params('from')." - ".$app->request->params('to');
    })->setName("model");    
    $app->delete('/(users|projects)/:id/models/:model/patches(/)', function() use($app)
    {
      echo " :patches : ".$app->request->params('from')." - ".$app->request->params('to');
    })->setName("model");
    $app->get('/(users|projects)/:id/models/:model/editor(/)', function() use($app)
    {
      if (!$app->resource->exists())
        $app->halt(STATUS_NOT_FOUND);
      else if (!$app->resource->canWrite($app->user))
        $app->halt(STATUS_FORBIDDEN);
      $luaURL = $app->resource->getWSUrl();
      $userToken = $app->resource->generateToken();
      $can_ok = true; // verify if server lua exist that port
      if ($can_ok)
      {
        // formToken {user_token, user_read, user_write} 
        $serverToken = $app->resource->getWSToken();
        // send to lua server 
      }
      else
      {
        //create server tocken and choice port and create server lua
        $serverToken = $app->resource->generateToken();
        $luaURL = "ws://localhost:300"; // Choice port
        //create lua server
        $app->resource->saveWSUrl($luaURL);
        $app->resource->saveWSToken($serverToken);
      }
      $app->response->headers->set('Content-Type','application/json');
      $app->response->setStatus(STATUS_OK);
      $app->response->setBody(json_encode(array('url' => $luaURL, 'token' => $userToken)));
    })->setName("model"); 

    $this->next->call();
  }
}

class ModelResource extends BaseResource
{
  public static function newResource($url)
  {
    return new ModelResource($url);
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
    else if($this->isEmptyDir($app->config["base_dir"].$this->getURL()))
    {
      $app->response->setStatus(STATUS_GONE);
      return null;
    }
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    $lua = file_get_contents($app->config["base_dir"].$this->getURL()."/model.lua");
    if ($lua == FALSE || $info == FALSE)
    {
      return null;
    } 
    $data = array ('name' => $info["name"], 'data' => $lua);
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $data;
  }

  public function write($data)
  {
    global $app;
    //Write ressource
    $is_ok = false;
    $info = "";
    $auth = "";
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$this->getURL()) ||   
        $this->isEmptyDir($app->config["base_dir"].$this->getURL()))
    {
      if(!file_exists($app->config["base_dir"].$this->getURL()))
      {
        mkdir($app->config["base_dir"].$this->getURL());
      }
      $tmp = array();
      mkdir($app->config["base_dir"].$this->getURL()."/patches");
      $tmp["name"] = "Patch list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/patches/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/editor");
      $tmp["name"] = "Editor information file";
      $tmp["token"] = "";
      $tmp["url"] = "";
      file_put_contents($app->config["base_dir"].$this->getURL()."/editor/info.json", json_encode($tmp));
      $app->response->setStatus(STATUS_CREATED);
    } 
    else 
      $app->response->setStatus(STATUS_OK);
    if (array_key_exists("name", $data))
      file_put_contents($app->config["base_dir"].$this->getURL()."/info.json", json_encode(array('name' => $data["name"])));
    if (array_key_exists("data", $data))
      file_put_contents($app->config["base_dir"].$this->getURL()."/model.lua", $data["data"]);
    return $is_ok = true; 
  }

  public function writePatch($data)
  {
    global $app;
    $is_ok = false;
    $info = "";
    $auth = "";
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$this->getURL()) ||   
        $this->isEmptyDir($app->config["base_dir"].$this->getURL()))
    {
      if(!file_exists($app->config["base_dir"].$this->getURL()))
      {
        mkdir($app->config["base_dir"].$this->getURL());
      }
      $tmp = array();
      mkdir($app->config["base_dir"].$this->getURL()."/patches");
      $tmp["name"] = "Patch list";
      file_put_contents($app->config["base_dir"].$this->getURL()."/patches/info.json", json_encode($tmp));
      mkdir($app->config["base_dir"].$this->getURL()."/editor");
      $tmp["name"] = "Editor information file";
      file_put_contents($app->config["base_dir"].$this->getURL()."/editor/info.json", json_encode($tmp));
      $app->response->setStatus(STATUS_CREATED);
    } 
    else 
      $app->response->setStatus(STATUS_OK);
    $pathInfo = pathinfo($app->config["base_dir"].$this->getURL());
    $info = array('name' => $pathInfo['basename']);
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json", json_encode($info));
    file_put_contents($app->config["base_dir"].$this->getURL()."/model.lua", $data);
    return $is_ok = true; 
  }

  public function readPatch()
  {
    global $app;
    //Verify existing resource
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$this->getURL()))
    {
      // Resource not found
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    $lua = file_get_contents($app->config["base_dir"].$this->getURL());
    if ($lua == FALSE)
    {
      return null;
    } 
    $pathInfo = pathinfo($app->config["base_dir"].$this->getURL(), PATHINFO_FILENAME);
    $data = array ('name' => $pathInfo["filename"], 'data' => $lua);
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $data;    
  }

  public function readPatchList($from, $to)
  {
    global $app;
    $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    if (!file_exists($app->config["base_dir"].$this->getURL()))
    {
      $app->response->setStatus(STATUS_NOT_FOUND);
      return null;
    } 
    $data = array();
    foreach(glob($app->config["base_dir"].$this->getURL(). '/*') as $file) 
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
    $app->response->setStatus(STATUS_OK);
    $app->response->headers->set('Content-Type','application/json');
    return $data;    
  }

  public function generateToken()
  {
    return uniqid("cosyVerif", true);
  }

  public function saveWSToken($data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    $info["token"] = $data;
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json", json_encode($info));
    return true;
  }

  public function getWSToken()
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    return $info["token"];
  }

  public function saveWSUrl($data)
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    $info["url"] = $data;
    file_put_contents($app->config["base_dir"].$this->getURL()."/info.json", json_encode($info));
    return true;
  }

  public function getWSUrl()
  {
    global $app;
    $info = json_decode(file_get_contents($app->config["base_dir"].$this->getURL()."/info.json"), TRUE);
    return $info["url"];
  }

  public function formToken($token_user)
  {

  }
}