<?php
require_once 'Constants.php';
require_once 'Util.php';
use GuzzleHttp\Stream;

class PermissionTest extends PHPUnit_Framework_TestCase
{

  public function testGetUser()
  {
    Util::addUserRoot(RESOURCE_PRIVATE);
    Util::addUser("Gael", "Thomas", "gthomas", "toto", USER_LIMIT, RESOURCE_PRIVATE);
    Util::addUser("Toto", "Sow", "tsow", "toto", USER_LIMIT, RESOURCE_PUBLIC);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $res = $client->get('http://localhost:8080/server.php/users/root', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->get('http://localhost:8080/server.php/users/root', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //not authentified user
    $res = $client->get('http://localhost:8080/server.php/users/root', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
  }

  public function testPutUser()
  {
    Util::addUserRoot(RESOURCE_PRIVATE);
    Util::addUser("Gael", "Thomas", "gthomas", "toto", USER_LIMIT, RESOURCE_PRIVATE);
    Util::addUser("Toto", "Sow", "tsow", "toto", USER_LIMIT, RESOURCE_PUBLIC);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}']);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}',
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    //not authentified user
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}',
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }

  public function testDeleteUser()
  {
    Util::addUserRoot(RESOURCE_PRIVATE);
    Util::addUser("Gael", "Thomas", "gthomas", "toto", USER_LIMIT, RESOURCE_PRIVATE);
    Util::addUser("Toto", "Sow", "tsow", "toto", USER_LIMIT, RESOURCE_PUBLIC);
    $client = new GuzzleHttp\Client();
    //Administrator use get users
    $encoded = base64_encode("root:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}']);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_NO_CONTENT, $res->getStatusCode()); 
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}',
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    //not authentified user
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}',
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/tsow', ['exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }
}