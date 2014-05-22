<?php

use GuzzleHttp\Stream;

class AddUserTest extends PHPUnit_Framework_TestCase
{
    // ...

  public function testUserCreated()
  {
    $client = new GuzzleHttp\Client();
    $client->delete('http://localhost:8080/server.php/users/tsow',
                    ['exceptions' => false]);
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow"}'//,
                         //'debug' => true
                        ]);
    $this->assertEquals(201, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', ['headers' => ['Accept' => 'application/json']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(200, $res->getStatusCode()); 
    $this->assertEquals("Tata", $data["first_name"]); 
  }

  public function testUserUpdating()
  {  
    $client = new GuzzleHttp\Client();
    $client->delete('http://localhost:8080/server.php/users/tsow',
                    ['exceptions' => false]);
    $client->put('http://localhost:8080/server.php/users/tsow', 
                 ['headers' => ['Content-Type' => 'application/json'],
                  'body' => '{"first_name" : "Tata","last_name" : "Sow", "login" :"tsow"}',
                  'exceptions' => false]);
    $res = $client->put('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "Titi","last_name" : "Sow", "login" :"tsow"}'
                        ]);
    $this->assertEquals(200, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/tsow', 
                        ['headers' => ['Accept' => 'application/json']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(200, $res->getStatusCode()); 
    $this->assertEquals("Titi", $data["first_name"]);  
  }

}