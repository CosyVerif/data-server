<?php

namespace CosyVerif\Server;

class Core
{

	private $app;

  public function __construct()
  {
    global $app;

    $app->get('/(users|projects)/:id', function($id) use($app){
      $data = $this->get($app->request->getResourceUri());
      if(!is_null($data)){$app->response->setBody($data);}
    });

    $app->put('/(users|projects)/:id', function($id) use($app){

      $data = $app->request->put("body");
      //echo $data;
      //$this->put($app->request->getResourceUri(), $data);
      //$app->response->setStatus(201);
      //$app->response->setBody($data);
      //$app->response->headers->set('Content-Type' : 'application/json');
    });

  }

  /**
     * GET method.
     * 
     * @param  url
     * @return json or cosy format
     */
  public function get($url)
  { 
    $resource = StreamJson::read($url);  
    if(is_array($resource)){
      $resource = json_encode($resource);
    }
    return $resource;
  }

  /**
     * PUT method.
     * 
     * @param  url, data
     * @return boolean
     */
  public function put($url, $data)
  {

    $is_ok = StreamJson::write($url, $data);

    return $is_ok;
  }
}