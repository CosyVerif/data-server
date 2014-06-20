<?php

use GuzzleHttp\Stream;

class AuthentificationTest extends PHPUnit_Framework_TestCase
{

  public function testAcceptAuthentification()
  {
    $config = Util::getConfig();
    Util::addUserRoot();
    Util::addUser("test", "pass", "gthomas", "toto", true, true);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
  }

  public function testNotAcceptAuthentification()
  {
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("sisi:totoi");
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_UNAUTHORIZED, $res->getStatusCode());
  }

  public function testNotProvidedInformationAuth()
  {
    $client = new GuzzleHttp\Client();
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
  }
}