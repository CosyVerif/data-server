<?php

namespace CosyVerif\Server;

class CrossOrigin extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new CrossOrigin());
  }

  public function call()
  {
    global $app;
    $this->next->call();
    $response = $app->response();
    $response->header('Access-Control-Allow-Origin', '*');
  }
}

