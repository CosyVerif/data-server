<?php
require_once 'Constants.php';
require_once 'Util.php';
use GuzzleHttp\Stream;

class PermissionTest extends PHPUnit_Framework_TestCase
{

  public function testGetUser()
  {
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true, true, true);
    Util::addUser("Toto", "Sow", "tsow", "toto", true,true,false, false);
    Util::addUser("Nana", "Nana", "nnana", "toto", true,true,false, true);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/nnana', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //not authentified user
    $res = $client->get('http://localhost:8080/server.php/users/root', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
  }

  public function testPutUser()
  {
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true, true, true);
    Util::addUser("Toto", "Sow", "tsow", "toto", true,true,false, false);
    Util::addUser("Nana", "Nana", "nnana", "toto", false,false,false, true);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $body = array('info' => array('first_name' => 'Tata', 'last_name' => 'Sow'),
                  'auth' => array('login' => 'tsow', 
                                  'password' => 'toto',
                                  'permissions' => array("user-create" => true, 
                                                         "user-modify" => true, 
                                                         "user-delete" => true),
                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("nnana:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $body = array('info' => array('first_name' => 'Nanas', 'last_name' => 'Nanas'),
                  'auth' => array('login' => 'nnana', 
                                  'password' => 'toto',
                                  'permissions' => array("user-create" => true, 
                                                         "user-modify" => true, 
                                                         "user-delete" => true),
                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/nnana', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //not authentified user
    $body = array('info' => array('first_name' => 'Tata', 'last_name' => 'Sow'),
                  'auth' => array('login' => 'tsow', 
                                  'password' => 'toto',
                                  'permissions' => array("user-create" => true, 
                                                         "user-modify" => true, 
                                                         "user-delete" => true),
                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }

  public function testDeleteUser()
  {
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true, false, false);
    Util::addUser("Toto", "Sow", "tsow", "toto", true,true,false, true);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $body = array('info' => array('first_name' => 'Tata', 'last_name' => 'Sow'),
                  'auth' => array('login' => 'tsow', 
                                  'password' => 'toto',
                                  'permissions' => array("user-create" => true, 
                                                         "user-modify" => true, 
                                                         "user-delete" => true),
                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_NO_CONTENT, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    //not authentified user
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow', ['exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }
}