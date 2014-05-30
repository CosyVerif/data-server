<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

class AddUserTest extends PHPUnit_Framework_TestCase
{

  public function testUserCreated()
  {
    Util::addUserRoot(RESOURCE_PUBLIC);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}'//,
                         //'debug' => true
                        ]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("Tata", $data["first_name"]); 
  }

  public function testUserUpdating()
  {  
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("root:toto");
    $client->put('http://localhost:8080/server.php/users/tsow', 
                 ['headers' => ['Content-Type' => 'application/json', 
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow","password" : "toto"}',
                  'exceptions' => false]);
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "Titi","last_name" : "Sow", "login" :"tsow","password" : "toto"}'
                        ]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("Titi", $data["first_name"]);  
  }

}