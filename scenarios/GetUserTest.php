<?php

use GuzzleHttp\Stream;
require_once 'Constants.php';

class GetUserTest extends PHPUnit_Framework_TestCase
{

  public function testUp(){
    $this->rrmdir("resources/users");
    $info = array('first_name' =>'Idrissa',
                  'last_name' => 'Sokhona');
    $auth = array('login' => 'isokhona',
                  'password' => password_hash('isokhonatoto', PASSWORD_DEFAULT));
    mkdir("resources/users/isokhona");
    file_put_contents("resources/users/isokhona/info.json", json_encode($info));
    file_put_contents("resources/users/isokhona/auth.json", json_encode($auth));
  }

  public function testUserFound()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
    $res = $client->get('http://localhost:8080/server.php/users/isokhona', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $this->assertEquals("Idrissa", $data["first_name"]);   
  }

  public function testUserNotFound()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
    $res = $client->get('http://localhost:8080/server.php/users/other',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode());   
  }

  public function testUserDeleted()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
    $client->put('http://localhost:8080/server.php/users/toto', 
                 ['headers' => ['Content-Type' => 'application/json',
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => '{"first_name" : "Tata","last_name" : "Oto", 
                              "login" :"toto","password" : "toto"}']);
    $res = $client->delete('http://localhost:8080/server.php/users/toto',
                           ['headers' =>['Authorization' => 'Basic '.$encoded.'==']]);
    $res = $client->get('http://localhost:8080/server.php/users/toto',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    $this->assertEquals(STATUS_GONE, $res->getStatusCode());   
  }

  private function rrmdir($dir){
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            $this->rrmdir($file);
        else
            unlink($file);
    }

    if($dir != "resources/users")
      rmdir($dir);
  }
}

