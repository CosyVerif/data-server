<?php

namespace CosyVerif\Server;

class Routing
{
  public static function register()
  {
    global $app;
    
    Routing\HeaderMiddleware    ::register();
    Routing\UserMiddleware      ::register();
    Routing\ProjectMiddleware   ::register();
    Routing\SearchMiddleware    ::register();
    Routing\ExceptionMiddleware ::register();
  }
  public function call()
  {
    $this->next->call();
  }
}
