<?php

use GuzzleHttp\Stream;
require_once 'Constants.php';

class GetUserTest extends PHPUnit_Framework_TestCase
{

  public function testUserFound()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("Gael", $data["first_name"]);   
  }

  public function testUserNotFound()
  {
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $res = $client->get('http://localhost:8080/server.php/users/other',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode());   
  }

  public function testUserDeleted()
  {
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $body = array('info' => array('first_name' => 'Tata', 'last_name' => 'Oto'),
                  'auth' => array('login' => 'toto', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $client->put('http://localhost:8080/server.php/users/toto', 
                 ['headers' => ['Content-Type' => 'application/json',
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => json_encode($body)]);
    $res = $client->delete('http://localhost:8080/server.php/users/toto',
                           ['headers' =>['Authorization' => 'Basic '.$encoded.'==']]);
    $res = $client->get('http://localhost:8080/server.php/users/toto',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_GONE, $res->getStatusCode());   
  }
}

