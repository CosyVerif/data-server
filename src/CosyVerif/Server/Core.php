<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Core  extends \Slim\Middleware
{
  public function call()
  {
    global $app;

    $app->get('/(users|projects)/:id', function($id) use($app){
      $data = $this->get($app->request->getResourceUri());
      if (!is_null($data)){$app->response->setBody($data);}
    });

    $app->put('/(users|projects)/:id', function($id) use($app){
      $data = json_decode($app->request->getBody(), TRUE);
      if (!is_array($data)){
        $app->halt(STATUS_UNPROCESSABLE_ENTITY);
      }
      $this->put($app->request->getResourceUri(), $data);
    });

    $app->delete('/(users|projects)/:id', function($id) use($app){
      $this->delete($app->request->getResourceUri());
    });

    $this->next->call();
  }

  public function get($url)
  { 
    $resource = StreamJson::read($url);  
    if (is_array($resource)){
      $resource = json_encode($resource);
    }
    return $resource;
  }

  public function put($url, $data)
  {
    $is_ok = StreamJson::write($url, $data);
    return $is_ok;
  }

  public function delete($url)
  {
    $is_ok = StreamJson::delete($url);
    return $is_ok;
  }
}