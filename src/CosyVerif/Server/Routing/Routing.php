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
    ModelMiddleware::register();
    FormalismMiddleware::register();
    ServiceMiddleware::register();
    ExecutionMiddleware::register();
    ScenarioMiddleware::register();
    IdeaMiddleware::register();
    DiscussionMiddleware::register();
    WebSiteMiddleware::register();
    SearchMiddleware::register();
    ExceptionMiddleware::register();
  }
  public function call()
  {
    $this->next->call();
  }
}