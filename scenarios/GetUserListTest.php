<?php

use GuzzleHttp\Stream;
require_once 'Constants.php';

class GetUserListTest extends PHPUnit_Framework_TestCase
{

  public function testUserListFound()
  {
    Util::addUserRoot();
    Util::addUser("Gael", "Thomas", "gthomas", "toto", true, true, true, true);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $res = $client->get('http://localhost:8080/server.php/users', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());   
  }

  public function testUserListNotFound()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $res = $client->get('http://localhost:8080/server.php/userss',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode());   
  }
}

