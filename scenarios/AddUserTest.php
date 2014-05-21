<?php

use GuzzleHttp\Stream;

class TestAddUser extends PHPUnit_Framework_TestCase
{
    // ...

  public function testUserCreated()
  {
    
    $client = new GuzzleHttp\Client();
    $res = $client->put('http://localhost:8080/server.php/users/rokysaroi', 
                        ['headers' => ['Content-Type' => 'application/json'],
                         'body' => '{"first_name" : "tata","last_name" : "sow", "login" :"tsow"}',
                         'debug' => true
                        ]);

    //echo $res->getBody();
    //echo $res->getHeader('content-type');
    

  }

}