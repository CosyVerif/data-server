<?php

namespace CosyVerif\Server;
require_once 'Constants.php';

class Core  extends \Slim\Middleware
{
  public function call()
  {
    global $app;

    $app->group('/', function () use ($app) 
    {
        $app->group('users(/)', function () use ($app) 
        {
            $app->group('/:userID(/)', function () use ($app) 
            {
                $app->group('/projects(/)', function () use ($app) 
                {
                    $app->get('', function() use($app){
                      echo " :projects: ";
                    })->setName("get-user-resourceList");
                    $app->get('/:projectID+', function() use($app, $projectID){})->setName("user-project");
                    $app->put('/:projectID+', function() use($app, $projectID){})->setName("user-project");
                    $app->patch('/:projectID+', function() use($app, $projectID){})->setName("user-project");
                    $app->delete('/:projectID+', function() use($app, $projectID){})->setName("user-project");
                });
                $app->group('/:others(/)', function () use ($app) 
                {
                    $app->group('/:other(/)', function () use ($app) 
                    {
                      $app->get('', function() use($app){
                        echo " :other: ";
                      })->setName("user-resource");
                    });
                    $app->get('', function() use($app){
                      echo " :others: ";
                    })->setName("user-resourceList");

                });
                $app->get('', function() use($app){
                    $data = $this->get($app->request->getResourceUri(), true);
                    if (!is_null($data)){$app->response->setBody($data);}
                })->setName("user");
                $app->put('', function() use($app){
                    $data = json_decode($app->request->getBody(), TRUE);
                    if (!is_array($data)){
                      $app->halt(STATUS_UNPROCESSABLE_ENTITY);
                    }
                    $this->put($app->request->getResourceUri(), $data);
                })->setName("user");
                $app->patch('', function() use($app){
                    
                })->setName("user");
                $app->delete('', function() use ($app){
                  $this->delete($app->request->getResourceUri());
                })->setName("user");
            });
          $app->get('', function() use($app){
            $data = $this->get($app->request->getResourceUri(), true);
            if (!is_null($data)){$app->response->setBody($data);}
          })->setName("list");
        });
        $app->group('projects(/)', function () use ($app) 
        {
            $app->group('/:projectID(/)', function () use ($app) 
            {
                $app->group('/users(/)', function () use ($app) 
                {
                    $app->get('', function() use($app){
                      echo " :users: ";
                    })->setName("get-project-resourceList"); 
                    $app->get('/:projectUserID(/)', function() use($app){})->setName("project-user"); 
                    $app->put('/:projectUserID(/)', function() use($app){})->setName("project-user"); 
                    $app->patch('/:projectUserID(/)', function() use($app){})->setName("project-user"); 
                    $app->delete('/:projectUserID(/)', function() use($app){})->setName("project-user"); 
                    $app->get('/:projectUserID/:other+', function() use($app){})->setName("project-user-resources"); 
                    $app->put('/:projectUserID/:other+', function() use($app){})->setName("project-user-resource"); 
                    $app->patch('/:projectUserID/:other+', function() use($app){})->setName("project-user-resource"); 
                    $app->delete('/:projectUserID/:other+', function() use($app){})->setName("project-user-resource"); 

                });
                $app->group('/:others(/)', function () use ($app) 
                {
                    $app->group('/:otherID', function () use ($app) 
                    {
                        $app->get('', function() use($app){
                          echo " :other: ";
                        }); 
                    });
                    $app->get('', function() use($app){
                      echo " :others: ";
                    })->setName("project-resourceList");           
                });
                $app->get('', function() use($app){
                  echo " :project: ";
                })->setName("project");          
            });
            $app->get('', function() use($app){
              $data = $this->get($app->request->getResourceUri(), true);
              if (!is_null($data)){$app->response->setBody($data);}
            })->setName("list");        
        });
        $app->get('', function() use($app){
          $data = $this->get($app->request->getResourceUri(), true);
          if (!is_null($data)){$app->response->setBody($data);}
        })->setName("list");
    });
    /*
    $app->get('/users/:id', function($id) use($app){
      $data = $this->get($app->request->getResourceUri(), false);
      if (!is_null($data)){$app->response->setBody($data);}
    });

    $app->get('/projects/:id', function($id) use($app){
      $data = $this->get($app->request->getResourceUri(), false);
      if (!is_null($data)){$app->response->setBody($data);}
    });

    $app->get('/(users|projects)', function() use($app){
      $data = $this->get($app->request->getResourceUri(), true);
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
*/
    $this->next->call();
  }

  private function get($url,$isList)
  { 
    if ($isList){
      $resource = StreamJson::readResourceList($url); 
    } else {
      $resource = StreamJson::readResource($url); 
    }
    if (is_array($resource)){
      $resource = json_encode($resource);
    }
    return $resource;
  }

  private function put($url, $data)
  {
    $is_ok = StreamJson::write($url, $data);
    return $is_ok;
  }

  private function delete($url)
  {
    $is_ok = StreamJson::delete($url);
    return $is_ok;
  }
}