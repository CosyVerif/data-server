<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

class AddUserTest extends PHPUnit_Framework_TestCase
{

  public function testUserCreated()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $body = array('info' => array('first_name' => 'user', 'last_name' => 'not_exist'),
                  'auth' => array('login' => 'user_not_exist', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/user_not_exist', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/user_not_exist', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("user", $data["first_name"]); 
  }

  public function testUserUpdating()
  {  
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $body = array('info' => array('first_name' => 'user', 'last_name' => 'exist'),
                  'auth' => array('login' => 'user_exist', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $client->put('http://localhost:8080/server.php/users/user_exist', 
                 ['headers' => ['Content-Type' => 'application/json', 
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => json_encode($body),
                  'exceptions' => false]);
    $body = array('info' => array('first_name' => 'users', 'last_name' => 'exist'),
                  'auth' => array('login' => 'user_exist', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/user_exist', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/user_exist', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("users", $data["first_name"]);  
  }
}