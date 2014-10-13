<?php
namespace CosyVerif\Server\Routing;

class SearchMiddleware  extends \Slim\Middleware
{
  public static function register()
  {
    global $app;
    $app->add(new SearchMiddleware());
  }
  public function call()
  {
    global $app;

    $app->get('/search(/)', function() use($app)
    {
      // {urls:{"/users","/users/idrissa"}, types:{"formalisms","users","formalisms"}, value : "idrissa"}
      $urls = explode(',', $app->request->params("urls"));
      $types = explode(',', $app->request->params("types"));
      $value = $app->request->params("value");
      $searchResult = array();
      if ($urls[0] == "/")
      {
        $searchResult = SearchResource::newResource(null)->getAll($types, $value);
      }
      else
      {
        $searchResult = SearchResource::newResource(null)->getParties($urls, $types, $value);
      }
      $searchResult = SearchResource::newResource(null)->sort(SearchResource::newResource(null)->eliminateDuplicate($searchResult), true);
      $app->response->setBody(json_encode($searchResult));
    });

    $this->next->call();
  }
}

class SearchResource extends BaseResource
{
  public static function newResource($url)
  {
    return new SearchResource($url);
  }

  public function getAll($types, $value)
  {
    $projectList = array();
    $users = UserResource::newResource("/users")->readList();
    $projectList = array_merge($projectList, $users["resource_list"]);
    $projects = ProjectResource::newResource("/projects")->readList();
    $projectList = array_merge($projectList, $projects["resource_list"]);
    $resourceList = array();
    foreach ($projectList as $key => $object) {
      $resourceList = array_merge($resourceList, $this->getResources($object["href"], $types, $value));
    }
    if (in_array("users", $types)){
      $resourceList = array_merge($resourceList, $this->search($value, $users["resource_list"]));
    }
    if (in_array("projects", $types)){
      $resourceList = array_merge($resourceList, $this->search($value, $projects["resource_list"]));
    }
    foreach ($resourceList as $key => $resource) {
      $resourceList[$key] = $this->addInformations($resource);
    }
    return $resourceList;
  }

  public function getParties($urls ,$types, $value)
  {
    $resourceList = array();
    foreach ($urls as $key => $url) 
    {
      if (BaseResource::newResource($url)->file_exists())
        $resourceList = array_merge($resourceList, $this->getResources($url, $types, $value));
    }
    foreach ($resourceList as $key => $resource) 
    {
      $resourceList[$key] = $this->addInformations($resource);
    }
    return $resourceList;
  }

  public function getResources($url, $types, $value)
  {
    global $app;
    $resourceList = array();
    foreach ($types as $key => $type) 
    {
      $object = BaseResource::newResource($url."/".$type);
      if (!$object->file_exists())
        continue;
      else if (!$object->canRead($app->user))
        continue;
      if ($type == "users" && $object->getURLBase() == "projects")
      {
        $list = ProjectResource::newResource($url."/".$type)->user_readList();
        $result = $list["resource_list"];
      }
      else if ($type == "projects" && $object->getURLBase() == "users")
      {
        $list = UserResource::newResource($url."/".$type)->project_readList();
        $result = $list["resource_list"];
        foreach ($list["resource_list"] as $keyUser => $valueUser) 
        {
          $result = array_merge($result, $this->getResources($valueUser["href"], $types, $value));
        }
      }
      else if ($type != "projects" && $type != "users")
      {
        $list = HeaderResource::newResource($url."/".$type)->readList();
        $result = $list["resource_list"];
      }
      $resourceList = array_merge($resourceList, $this->search($value, $result)); 
    }
    return $resourceList; 
  }

  public function search($value, $array)
  {
    if ($value == "")
      return $array;
    $pattern = '#'.strtolower($value).'#';
    foreach ($array as $key => $object) 
    {
      if (isset($object["name"]) && isset($object["description"]) && !preg_match($pattern, strtolower($object["name"])) && !preg_match($pattern, strtolower($object["description"])))
      {
        unset($array[$key]);
      }
    }
    return $array;
  }

  public function sort($array, $isASC)
  {
    for ($i=0; $i < count($array) - 1 ; $i++) 
    { 
      $tmp1 = $array[$i];
      for ($j=$i+1; $j < count($array); $j++) 
      { 
        $tmp2 = $array[$j];
        if ((strcasecmp($tmp2["name"], $tmp1["name"]) < 0 && $isASC) || (strcasecmp($tmp2["name"], $tmp1["name"]) > 0 && !$isASC))
        {
          $array[$i] = $tmp2;
          $array[$j] = $tmp1;
          $tmp1 = $tmp2;
        }
      }
    }
    return $array;
  }

  public function eliminateDuplicate($array)
  {
    $newArray = array();
    for ($i=0; $i < count($array); $i++) 
    { 
      $isDuplicate = false;
      $tmp1 = $array[$i];
      for ($j=0; $j < count($newArray); $j++) 
      { 
        $tmp2 = $newArray[$j];
        if ($tmp1["href"] == $tmp2["href"])
        {
          $isDuplicate = true;
          break;
        }
      }
      if (!$isDuplicate)
        $newArray[] = $array[$i];
    }
    return $newArray;
  }
}