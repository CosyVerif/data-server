<?php

namespace CosyVerif\Server\Routing;

class WebSiteMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new WebSiteMiddleware());
  }
  public function call()
  {
    global $app;

    $app->get('/(webclient|html|js|lua|css|img)(/:name+)', function() use($app)
      {
        $url = $app->request->getResourceUri();
        if ($url == "/")
          $this->website("webclient/index.html");
        else if (substr($url, 1,9) == "webclient")
          $this->website(substr($url, 1));
        else
          $this->website("webclient".$url);
      })->setName("webclient");

    $this->next->call();
  }

  private function website($url)
  {
    global $app;
    $data = file_get_contents($url);
    if ($data == FALSE)
    {
      $app->response->setStatus(STATUS_INTERNAL_SERVER_ERROR);
    }
    else
    {
      $app->response->setStatus(STATUS_OK);
      $app->response->setBody($data);
      $pathInfo = pathinfo($url);
      $extension = $pathInfo['extension'];
      if ($extension == "html")
      {
        $app->response->headers->set('Content-Type','text/html');
      }
      else if ($extension == "css")
      {
        $app->response->headers->set('Content-Type','text/css');
      }
      else if ($extension == "js")
      {
        $app->response->headers->set('Content-Type','application/javascript');
      }
      else if ($extension == "jpg")
      {
        $app->response->headers->set('Content-Type','image/jpeg');
      } 
      else if ($extension == "png")
      {
        $app->response->headers->set('Content-Type','image/png');
      } 
      
    }
  }
}