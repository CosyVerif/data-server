<?php

use GuzzleHttp\Stream;
require_once 'Constants.php';

class DeleteUserTest extends PHPUnit_Framework_TestCase
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

  public function testDeleteExistUser()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
    $res = $client->put('http://localhost:8080/server.php/users/udelete', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => '{"first_name" : "User","last_name" : "Delete", 
                                     "login" :"udelete","password" : "toto"}'
                        ]);
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
    $encoded = base64_encode("isokhona:toto");
    $res = $client->delete('http://localhost:8080/server.php/users/udelete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode()); 
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