<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;


// # User Authentication Test
// 
// 
// This test covers user authentification. Many 
// cases (Success cases  or failure cases  or 
// errors cases ) can have present itself to us : 
// 
// Success cases:
//   -------------
// 1. User authentified,
// 2. User not authentified.
// 
// Failure cases :
// -------------
// 1. Authentified user failure.
// 
// Errors cases :
// ------------
// 1. Query contains syntax errors,
// 2. Internal server error.


class AuthentificationTest extends PHPUnit_Framework_TestCase
{
// Success cases
// -------------


// ##### User authentified
// This test authentificates a user in to server. The test
// user is user root. This test start in create a new 
// resource manually for the test. Good username and password 
// is required (username : root/password : toto). If this test
// passed the server returns `status code 200` (Success).

  public function testUserAuthentified()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new resource : user */
    Util::addUser("user_authentied", "user_authentied", "user_authentied", "toto", true, true);
    $client = new GuzzleHttp\Client();
    /* Prepares the username and password */
    $encoded = base64_encode($config["user_root"].":toto");
    /* Get user resource by providing username and password */
    $res = $client->get('http://localhost:8080/server.php/users/user_authentied', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
  }


// ##### User not authentified
// This test user not provided a username and password. the 
// server accepted the public resource use by any user. So,
// the server satisfied the query. In the test, it creates
// a new user and get its without provided a username and 
// password.

  public function testUserNotAuthentified()
  {
    /* Add a new user */
    Util::addUser("user", "user", "user", "toto", true, true);
    $client = new GuzzleHttp\Client();
    /* Get user without provided username and password */
    $res = $client->get('http://localhost:8080/server.php/users/user', 
                        ['headers' => ['Accept' => 'application/json'],
                         'exceptions' => false]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
  }

// Failure cases
// -------------


// ##### Authentified user failure
// The request of the test does not pass because username 
// and/or password are incorrects. So, the server does not 
// satisfied the request and return `status code 401` 
// (Unauthorized) and it add `WWW-Authenticate` header 
// in the response.

  public function testNotAcceptAuthentification()
  {
    $client = new GuzzleHttp\Client();
    /* Get user with incorrects username and password */
    $encoded = base64_encode("sisi:totoi");
    $res = $client->get('http://localhost:8080/server.php/users/gthomas', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false]);
    /* Verify status code if that is 401 (Unauthorized) */
    $this->assertEquals(STATUS_UNAUTHORIZED, $res->getStatusCode());
  }

// Errors cases
// ------------



// ##### Query contains syntax errors



// ##### Internal server error

}