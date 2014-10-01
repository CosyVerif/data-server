<?php

namespace CosyVerif\Server\Routing;

class Routing
{
  public static function register()
  {
    global $app;

    \CosyVerif\Server\CrossOrigin::register();
    UserMiddleware::register();
    ProjectMiddleware::register();
    HeaderMiddleware::register();
    SearchMiddleware::register();
    ExceptionMiddleware::register();
  }
  public function call()
  {
    $this->next->call();
  }
}