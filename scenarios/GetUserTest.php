<?php

use GuzzleHttp\Stream;

class GetUserTest extends PHPUnit_Framework_TestCase
{
    // ...

  public function testUserFound()
  {
    $client = new GuzzleHttp\Client();
    $client->delete('http://localhost:8080/server.php/users/isokhona',
                    ['exceptions' => false]);
    $client->put('http://localhost:8080/server.php/users/isokhona', 
                 ['headers' => ['Content-Type' => 'application/json'],
                  'body' => '{"first_name" : "Idrissa","last_name" : "Sokhona", "login" :"isokhona"}'
                 ]);
    $res = $client->get('http://localhost:8080/server.php/users/isokhona', ['headers' => ['Accept' => 'application/json']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(200, $res->getStatusCode()); 
    $this->assertEquals("Idrissa", $data["first_name"]);   
  }

  public function testUserNotFound()
  {
    $client = new GuzzleHttp\Client();
    $client->delete('http://localhost:8080/server.php/users/other',
                    ['exceptions' => false]);
    $res = $client->get('http://localhost:8080/server.php/users/other',
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false
                        ]);
    $this->assertEquals(404, $res->getStatusCode());   
  }

  public function testUserDeleted()
  {
    $client = new GuzzleHttp\Client();
    $client->put('http://localhost:8080/server.php/users/toto', 
                 ['headers' => ['Content-Type' => 'application/json'],
                  'body' => '{"first_name" : "Tata","last_name" : "Oto", "login" :"toto"}'
                 ]);
    $res = $client->delete('http://localhost:8080/server.php/users/toto');
    $res = $client->get('http://localhost:8080/server.php/users/toto',
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false
                        ]);
    $this->assertEquals(410, $res->getStatusCode());   
  }
  /*
  public function testUnsupportedMediaType()
  {
    
    $client = new GuzzleHttp\Client();
    $res = $client->get('http://localhost:8080/server.php/users/other',['Accept' => 'text/html']);

    //$this->assertEquals(415, $res->getStatusCode());   

  }


  
  public function testIncorrectQuerySyntaxe()
  {
    
    $client = new GuzzleHttp\Client();
    $res = $client->get('http://localhost:8080/server.php/users/other');

    $this->assertEquals(404, $res->getStatusCode());   

  }
  */
}

