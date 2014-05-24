<?php

use GuzzleHttp\Stream;

class AuthentificationTest extends PHPUnit_Framework_TestCase
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

  public function testAcceptAuthentification()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("isokhona:toto");
    $res = $client->get('http://localhost:8080/server.php/users/isokhona', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
  }

  public function testNotAcceptAuthentification()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("sisi:totoi");
    $res = $client->get('http://localhost:8080/server.php/users/isokhona', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_UNAUTHORIZED, $res->getStatusCode());
  }

  public function testNotProvidedInformationAuth()
  {
    $client = new GuzzleHttp\Client();
    $res = $client->get('http://localhost:8080/server.php/users/isokhona', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
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