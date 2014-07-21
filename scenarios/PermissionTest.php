<?php
require_once 'Constants.php';
require_once 'Util.php';
use GuzzleHttp\Stream;


// # User Permissions Test
// 
// 
// This test covers user permissions test. many cases (cases 
// success or cases failure or cases errors) can have present 
// itself to us : 
// 
// Success cases :
// -------------
// 1. Root user uses user resource (:user),
// 2. User uses itselves resources,
// 3. User requests a resource and it have permission.
// 
// Failure cases :
// -------------
// 1. User requests a resource and it not have permission,
// 2. Requested resource is not found or the method is not implemented. 
// 
// Errors cases :
// ------------
// 1. Query contains syntax errors,
// 2. Internal server error.


class PermissionTest extends PHPUnit_Framework_TestCase
{
// Success cases
// -------------


// ##### Root user uses user resource (:user)
// This test treats the authentificate root user (root have 
// all permission on user resource).

  // __Root user get user : returns user data__
  public function testRootGetUser()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new resource : user */
    Util::addUser("get_user", "get_user", "get_user", "toto", true, true);
    $client = new GuzzleHttp\Client();
    /* Root user get user resource */
    $encoded = base64_encode($config["user_root"].":toto");
    $res = $client->get('http://localhost:8080/server.php/users/get_user', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    /* Verify status code 200 (success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
  }
  // __Root user put user : user created__
  public function testRootPutUser()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Prepares resource data : user data */
    $body = array('info' => array('first_name' => 'root_put', 'last_name' => 'root_put'),
                  'auth' => array('login' => 'root_put', 
                                  'password' => 'toto',
                                  'admin_user' => true,
                                  'can_public' => true));
    $res = $client->post('http://localhost:8080/server.php/users/root_put', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    /* Verify status code  201 (created) */
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
  }
  // __Root user delete user  : user deleted__
  public function testRootDeleteUser()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Prepares resource data : user data */
    $body = array('info' => array('first_name' => 'root_delete', 'last_name' => 'root_delete'),
                  'auth' => array('login' => 'root_delete', 
                                  'password' => 'toto',
                                  'admin_user' => true,
                                  'can_public' => true));
    $res = $client->post('http://localhost:8080/server.php/users/root_delete', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $res = $client->delete('http://localhost:8080/server.php/users/root_delete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_NO_CONTENT, $res->getStatusCode()); 
  }

  public function testGetUser()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true);
    Util::addUser("Toto", "Sow", "tsow", "toto", true, false);
    Util::addUser("Nana", "Nana", "nnana", "toto", true, true);
    $client = new GuzzleHttp\Client();
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
    $res = $client->get('http://localhost:8080/server.php/users/'.$config["user_root"], 
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
    $config = Util::getConfig();
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true);
    Util::addUser("Toto", "Sow", "tsow", "toto", true, false);
    Util::addUser("Nana", "Nana", "nnana", "toto", false, true);
    $client = new GuzzleHttp\Client();
    $body = array('info' => array('first_name' => 'user_put', 'last_name' => 'user_put'),
                  'auth' => array('login' => 'user_put', 
                                  'password' => 'toto',
                                  'admin_user' => true,
                                  'can_public' => true));
    //authentified user
    $encoded = base64_encode("nnana:toto");
    $res = $client->post('http://localhost:8080/server.php/users/user_put', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    $body = array('info' => array('first_name' => 'Nanas', 'last_name' => 'Nanas'),
                  'auth' => array('login' => 'nnana', 
                                  'password' => 'toto',
                                  'admin_user' => true,
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
                                  'admin_user' => true,
                                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }
/*
  public function testDeleteUser()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", false, false);
    Util::addUser("Toto", "Sow", "tsow", "toto", true, true);
    $client = new GuzzleHttp\Client();
    $body = array('info' => array('first_name' => 'user_delete', 'last_name' => 'user_delete'),
                  'auth' => array('login' => 'user_delete', 
                                  'password' => 'toto',
                                  'admin_user' => true,
                                  'can_public' => true));
    //authentified user
    $encoded = base64_encode("gthomas:toto");
    $res = $client->put('http://localhost:8080/server.php/users/user_delete', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/user_delete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
    //not authentified user
    $res = $client->put('http://localhost:8080/server.php/users/user_delete', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => json_encode($body),
                         'exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/user_delete', ['exceptions' => false]);
    $this->assertEquals(STATUS_FORBIDDEN, $res->getStatusCode()); 
  }
  */
}