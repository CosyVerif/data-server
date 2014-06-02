<?php

use GuzzleHttp\Stream;
require_once 'Constants.php';

class DeleteUserTest extends PHPUnit_Framework_TestCase
{
  public function testDeleteExistUser()
  {
    Util::addUserRoot(RESOURCE_PUBLIC);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $body = array('info' => array('first_name' => 'User', 'last_name' => 'Delete'),
                  'auth' => array('login' => 'udelete', 'password' => 'toto'));
    $res = $client->put('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $res = $client->delete('http://localhost:8080/server.php/users/udelete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_NO_CONTENT, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_GONE, $res->getStatusCode());  
  }

  public function testDeleteUserNotExist()
  {   
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $res = $client->delete('http://localhost:8080/server.php/users/udelete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode()); 
  }
}