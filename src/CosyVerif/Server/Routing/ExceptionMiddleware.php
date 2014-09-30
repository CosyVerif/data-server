<?php

namespace CosyVerif\Server\Routing;

class ExceptionMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new ExceptionMiddleware());
  }
  public function call()
  {
    global $app;

    try 
    {
      $this->next->call();
    }
    catch (\Exception $e)
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
      $app->response->setBody($e->getMessage());
    } 
  }
}