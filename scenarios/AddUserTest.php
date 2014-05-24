<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

class AddUserTest extends PHPUnit_Framework_TestCase
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

  public function testUserCreated()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
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
    $encoded = base64_encode("isokhona:toto");
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