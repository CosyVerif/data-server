<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;
/*  TODO LIST
  - Faire tous les test


*/

// #Test of adding a user
//
//
//This test covers adding, getting, deleting users. 
//many cases (Success cases or failure cases or 
//errors cases) can have present itself to us : 
//
// Success cases
// -------------
//
// ##### Edit mode
//
// 1. enter edit mode of a edit model started (get .../:model/editor). 
// 2. enter edit mode of a edit model not started (get .../:model/editor)
//
// ##### No edit mode
//
// 1. get a model (get .../:model),
// 2. get model list (get .../models)
// 3. put a model (put .../:model),
// 4. patch a model (patch .../:model),
// 5. delete a model (delete .../:model),
// 6. get patches (get .../:model/patches),
// 7. put patches (put .../:model/patches),
// 8. delete patches (delete .../:model/patches).
//
// Failure cases
// -------------
//
// ##### Edit mode          
//
// 1. Enter edit mode where model does not exist (get .../:model/editor).
// 
// ##### No edit mode
//
// 1. get a not found model (get .../:model),
// 2. patch a not found model (patch .../:model),
// 3. put a not found patches (put .../:model),
// 4. delete a not found/deleted model (delete .../:model),
// 5. get a not found patches (get .../:model/patches),
// 6. delete a not found patches (delete .../:model/patches).
//
// Errors cases
// --------------
//
// 1. Query contains syntax errors,
// 2. Internal server error.


class ModelTest extends PHPUnit_Framework_TestCase
{
// Success cases
// -------------

// ##### Enter edit mode of a edit model started
// This is enter edit mode test but edit model is started. This test start
// in create a new user (enter_edit_mode_user), a new model (model_1) and 
// start manually edit mode of the model (it is the condition for this 
// test). After, the client asks the server to enter edit mode model
// (get .../models/model_1/editor). After, the server returns Lua server
// url and port (editor information). After, verify status code if that is 
// `status code 200` (success) and verify response get data : Lua server 
// url and port.

  public function testEnterEditModeStarted()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("enter_edit_mode_user", "enter_edit_mode_user", "enter_edit_mode_user", "toto", true, true);

    /* Add a new model manually */
    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    Util::addModel("enter_edit_mode_user", "model_1", $model_data);
    /* Start manually edit mode of the model : model_1 */
    Util::enterEditMode("enter_edit_mode_user", "model_1", "127.0.0.1", 200);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("enter_edit_mode_user:toto");
    /* Client asks the server to switch to editing mode model : .../model_1/editor */
    $res = $client->get('http://localhost:8080/server.php/users/enter_edit_mode_user/models/model_1/editor', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify editor data :  url and port */
    $data = json_decode($res->getBody(), TRUE);
    $this->assertEquals("127.0.0.1", $data["url"]);
    $this->assertEquals(200, $data["port"]);      
  }

// ##### Enter edit mode of a edit model not started
// This is enter edit mode test but edit model not started. This test start
// in create a new user (enter_edit_mode_user) and a new model (model_1). 
// After, the server put the model in edit mode and returns to client
// Lua server url and port (editor information). After, the client asks the 
// server to enter edit mode model (get .../models/model_1/editor). After, 
// verify status code if that is `status code 200` (success) and verify 
// response get data : Lua server url and port.

  public function testEnterEditModeNotStarted()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("enter_edit_mode_user", "enter_edit_mode_user", "enter_edit_mode_user", "toto", true, true);

    /* Add a new model manually */
    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    Util::addModel("enter_edit_mode_user", "model_1", $model_data);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("enter_edit_mode_user:toto");
    /* Client asks the server to switch to editing mode model : .../model_1/editor */
    $res = $client->get('http://localhost:8080/server.php/users/enter_edit_mode_user/models/model_1/editor', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify editor data :  url and port */
    $data = json_decode($res->getBody(), TRUE);
    $this->assertEquals("127.0.0.1", $data["url"]);
    $this->assertEquals(300, $data["port"]);      
  }

// ##### Get a model (get .../:model)
// This is get a model in to server test. This test start in create a new
// user (get_model_user) and a new model (model_1) manually. After, it 
//get model (.../models/model_1) and verify status code if that is 
// `status code 200` (success) and verify model data.

  public function testGetModel()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("get_model_user", "get_model_user", "get_model_user", "toto", true, true);

    /* Add a new model manually */
    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    Util::addModel("get_model_user", "model_1", $model_data);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("get_model_user:toto");
    /* Get a model : model_1 */
    $res = $client->get('http://localhost:8080/server.php/users/get_model_user/models/model_1', 
                        ['headers' => ['Accept' => 'cosy/model', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify model data  */
    $data = $res->getBody();
    $this->assertEquals($model_data, $data);   
  }

  
}