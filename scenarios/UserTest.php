<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;


// # Test of adding a user
// 
// 
// This test covers adding, getting, deleting users. 
// many cases (Success cases or failure cases or 
// errors cases) can have present itself to us : 
// 
// Success cases
// -------------
// 1. add a new user,
// 2. update a user,
// 3. get a user,
// 4. get user list,
// 5. delete a user.
// 
// Failure cases
// -------------
// 1. get a not found user,
// 2. get a deleted user,
// 3. patch a not found user
// 4. delete a not found/deleted user.
// 
// Errors cases
// ------------
// 1. Query contains syntax errors,
// 2. Internal server error.


class UserTest extends PHPUnit_Framework_TestCase
{
// Success cases 
// --------------


// ##### Add a new user
// This test create a new user in to server. The query 
// is prepared and sends user data into server. The server
// creates user and returns to the client a 
// confirmation user creation by the `status code 201` 
// (created). The client verify if the status code is
// `status code 201`. And this test get user and
// verify corresponds to the first name and first name 
// that was sent.

  public function testAddNewUser()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Prepares user data    */
    $body = array('info' => array('first_name' => 'new_user', 'last_name' => 'new_user'),
                  'auth' => array('login' => 'new_user', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    /* Sends data into server by put method */
    $res = $client->post('http://localhost:8080/server.php/users/new_user', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    /* Verify status code  201 (created) */
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
    /* Get user for verify if user data exist in the server : verify first name */
    $res = $client->get('http://localhost:8080/server.php/users/new_user', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);   
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
    $data = json_decode($res->getBody(),TRUE); 
    $this->assertEquals("new_user", $data["first_name"]); 
  }


// ##### Update a user
// This test update a user in server. This test start
// in create a new user. After, it update user changing
// user data (first name is changed new_user => update_user) and 
// get user for verify if the data has been updated. 
// The server returns `status code 200` (Success) if 
// the user data has been updated.

  public function testUpdateUser()
  { 
    /* Prepares the request    */
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* New user data (first name is "new_user") and add a new user test   */
    $body = array('info' => array('first_name' => 'new_user', 'last_name' => 'new_user'),
                  'auth' => array('login' => 'update_user', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $client->post('http://localhost:8080/server.php/users/update_user', 
                 ['headers' => ['Content-Type' => 'application/json', 
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => json_encode($body),
                  'exceptions' => false]);
    /* Modify user data and update user   */
    $body = array('info' => array('first_name' => 'update_user', 'last_name' => 'update_user'),
                  'auth' => array('login' => 'update_user', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $res = $client->put('http://localhost:8080/server.php/users/update_user', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    /* Verify status code 200 (success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* get user for verify if user data is updating in the server : verify first name */
    $res = $client->get('http://localhost:8080/server.php/users/update_user', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals("update_user", $data["first_name"]);  
  }


// ##### Get a user
// This test get a user in server. This test start
// in create a new user manually. After, it get user 
// and verify status code if that is `status code 200` 
// (success) and verify user data. 

  public function testGetUser()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("get_user", "get_user", "get_user", "toto", true, true);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Get user get_user */
    $res = $client->get('http://localhost:8080/server.php/users/get_user', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify user data :  first name does get_user */
    $data = json_decode($res->getBody(),TRUE);
    $this->assertEquals("get_user", $data["first_name"]);   
  }


// ##### Get user list
// This test get all users in server. This test start
// in create tree new users manually. After, it get 
// users collection and verify status code if that is 
// `status code 200` (success). 

  public function testGetUserList()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add tree users   */
    Util::addUser("user_1", "user_1", "user_1", "toto", true, true);
    Util::addUser("user_2", "user_2", "user_2", "toto", true, true);
    Util::addUser("user_3", "user_3", "user_3", "toto", true, true);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Get users */
    $res = $client->get('http://localhost:8080/server.php/users', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode());   
  }


// ##### Delete a user
// This test delete a user in server. This test start
// in create a new user and verify if that has been
// created. After, it delete user and 
// get user for verify if the user has been deleted. 
// The server returns `status code 204` (no content) 
// if the user has been deleted. Get user deleted
// return the `status code 410` (Gone).

  public function testDeleteUser()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Add a new user */
    $body = array('info' => array('first_name' => 'user_delete', 'last_name' => 'user_delete'),
                  'auth' => array('login' => 'user_delete', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $res = $client->post('http://localhost:8080/server.php/users/user_delete', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => json_encode($body)]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode()); 
    /* get user for verify if user is created */
    $res = $client->get('http://localhost:8080/server.php/users/user_delete', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Delete user */
    $res = $client->delete('http://localhost:8080/server.php/users/user_delete',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 204 (No content) */
    $this->assertEquals(STATUS_NO_CONTENT, $res->getStatusCode());
    /* get user for verify if user has been deleted */
    $res = $client->get('http://localhost:8080/server.php/users/user_delete', 
                        ['headers' => ['Accept' => 'application/json',
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    /* Verify status code if that is 410 (Gone) */
    $this->assertEquals(STATUS_GONE, $res->getStatusCode());  
  }

// Failure cases
// -------------


// ##### Get a not found user
// This test get a not found user in server. Get any user 
// not found in serveur and verify status code if that is 
// `status code 404` (Not found). 

  public function testGetUserNotFound()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* get user other */
    $res = $client->get('http://localhost:8080/server.php/users/other',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    /* Verify status code if that is 404 (Not found) */
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode());   
  }


// ##### Get a deleted user
// This test get a deleted user in server. This test start
// in create a new user and delete its. After, get user and
// verify status code if that is `status code 410` (Gone).
// If get a deleted user, the server return status code
// 410 (Gone). 

  public function testGetDeletedUser()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* Add a new user */
    $body = array('info' => array('first_name' => 'user_delete', 'last_name' => 'user_delete'),
                  'auth' => array('login' => 'user_delete', 
                                  'password' => 'toto',
                                  'admin_user' => false,
                                  'can_public' => true));
    $client->post('http://localhost:8080/server.php/users/user_delete', 
                 ['headers' => ['Content-Type' => 'application/json',
                                'Authorization' => 'Basic '.$encoded.'=='],
                  'body' => json_encode($body)]);
    /* Delete user */
    $res = $client->delete('http://localhost:8080/server.php/users/user_delete',
                           ['headers' =>['Authorization' => 'Basic '.$encoded.'==']]);
    /* get user for verify if user has been deleted */
    $res = $client->get('http://localhost:8080/server.php/users/user_delete',
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'exceptions' => false
                        ]);
    /* Verify status code if that is 410 (Gone) */
    $this->assertEquals(STATUS_GONE, $res->getStatusCode());   
  }

 
// ##### Delete a not found/deleted user
// This test delete a not found user in server. The server
// returns `status code 404` (Not found). 

  public function testDeleteNotFoundUser()
  {  
    /* Prepares the request    */ 
    $config = Util::getConfig();
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode($config["user_root"].":toto");
    /* get user other */
    $res = $client->delete('http://localhost:8080/server.php/users/other',
                           ['headers' => ['Authorization' => 'Basic '.$encoded.'=='],
                            'exceptions' => false]);
    /* Verify status code if that is 404 (Not found) */
    $this->assertEquals(STATUS_NOT_FOUND, $res->getStatusCode());
  }

// Errors cases
// -------------


// ##### Query contains syntax errors


// ##### Internal server error
  
}