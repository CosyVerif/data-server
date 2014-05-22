<?php

use GuzzleHttp\Stream;

class DeleteUserTest extends PHPUnit_Framework_TestCase
{
    // ...

  public function testDeleteExistUser()
  {
    $client = new GuzzleHttp\Client();
    $res = $client->put('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "User","last_name" : "Delete", "login" :"udelete"}'
                        ]);
    $this->assertEquals(201, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/udelete', ['headers' => ['Accept' => 'application/json']]);
    $this->assertEquals(200, $res->getStatusCode()); 
    $res = $client->delete('http://localhost:8080/server.php/users/udelete');
    $this->assertEquals(204, $res->getStatusCode()); 
    $res = $client->get('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false
                        ]);
    $this->assertEquals(410, $res->getStatusCode());  
  }

  public function testDeleteUserNotExist()
  {   
    $client = new GuzzleHttp\Client();
    $client->delete('http://localhost:8080/server.php/users/udelete',
                    ['exceptions' => false]);
    $res = $client->delete('http://localhost:8080/server.php/users/udelete',
                           ['exceptions' => false]);
    $this->assertEquals(404, $res->getStatusCode()); 
  }
}