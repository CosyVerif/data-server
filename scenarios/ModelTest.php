<?php
require_once 'Constants.php';
use GuzzleHttp\Stream;

// #Test of adding a user
//
//
// This test covers adding, getting, deleting users. 
// many cases (Success cases or failure cases or 
// errors cases) can have present itself to us : 
//
// Success cases
// -------------
//
// ##### Edit mode
//
// 1. enter edit mode of a edit model started (get .../:model/editor),
// 2. enter edit mode of a edit model not started (get .../:model/editor).
//
// ##### No edit mode
//
// 1. get a model (get .../models/:model),
// 2. get model list (get .../models),
// 3. put a model (put .../models/:model),
// 4. patch a model (patch .../models/:model),
// 5. delete a model (delete .../models/:model),
// 6. get a patch (get .../models/:model/patches/:patch),
// 7. get patches (get .../models/:model/patches?from=...&to=...),
// 8. put a patch (put .../models/:model/patches/:patch),
// 9. delete a patch (delete .../models/:model/patches/:patch),
// 10. delete patches (delete .../models/:model/patches?from=...&to=...).
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
// 5. get a not found patch (get .../models/:model/patches/:patch),
// 6. get a not found patches (get .../:model/patches),
// 7. put a not found patch (put .../models/:model/patches/:patch),  
// 8. delete a not found patch (delete .../models/:model/patches/:patch),
// 9. delete a not found patches (delete .../:model/patches).
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
//
// #### Edit mode
//
// ##### Enter edit mode of a edit model started
// This is enter edit mode test but edit model is started. This test start
// in create a new user (enter_edit_mode_user), a new model (model_1) and 
// start manually edit mode of the model (it is the condition for this 
// test). After, the client asks the server to enter edit mode model
// (get .../models/model_1/editor). After, the server returns Lua server
// url, port, token (editor information). After, verify status code if that is 
// `status code 200` (success) and verify response get data : Lua server 
// url, port and token.

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
    Util::enterEditMode("enter_edit_mode_user", "model_1", "token_server", "ws://localhost:200");
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("enter_edit_mode_user:toto");
    /* Client asks the server to switch to editing mode model : .../model_1/editor */
    $res = $client->get('http://localhost:8080/server.php/users/enter_edit_mode_user/models/model_1/editor', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify editor data :  token, url and port */
    $data = json_decode($res->getBody(), TRUE);
    $this->assertTrue(array_key_exists("token", $data));
    $this->assertEquals("ws://localhost:200", $data["url"]);      
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
    /* Start manually edit mode of the model : model_1 */
    Util::enterEditMode("enter_edit_mode_user", "model_1", "token_server", "ws://localhost:300");
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("enter_edit_mode_user:toto");
    /* Client asks the server to switch to editing mode model : .../model_1/editor */
    $res = $client->get('http://localhost:8080/server.php/users/enter_edit_mode_user/models/model_1/editor', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify editor data :  token and port */
    $data = json_decode($res->getBody(), TRUE);
    $this->assertTrue(array_key_exists("token", $data));
    $this->assertEquals("ws://localhost:300", $data["url"]);      
  }

// #### No edit mode
//
// ##### Get a model (get .../models/:model)
// This is get a model in to server test. This test start in create a new
// user (get_model_user) and a new model (model_1) manually. After, it 
// get model (.../models/model_1) and verify status code if that is 
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
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    /* Verify model data  */
    $data = json_decode($res->getBody(), TRUE);
    $this->assertEquals("model_1", $data["name"]);  
    $this->assertEquals($model_data, $data["data"]);  
  }

// ##### Get model list (get .../:user/models)
// This is get all models of a user test. This test start in create 
// a new user (get_modelList_user) and tree new models manually. 
// After, it get model list (.../models) and verify status code 
// if that is `status code 200` (success). 

  public function testGetModelList()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("get_modelList_user", "get_modelList_user", "get_modelList_user", "toto", true, true);

    /* Add tree new model manually */
    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    Util::addModel("get_modelList_user", "model_1", $model_data);
    Util::addModel("get_modelList_user", "model_2", $model_data);
    Util::addModel("get_modelList_user", "model_3", $model_data);
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("get_modelList_user:toto");
    /* Get model list : model_1, model_2, model_3 */
    $res = $client->get('http://localhost:8080/server.php/users/get_modelList_user/models', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);
    /* Verify status code if that is 200 (Success) */
    $this->assertEquals(STATUS_OK, $res->getStatusCode());   
  }

  
// ##### Put a model (put .../models/:model)
// This is put a model test. This test start in create 
// a new user (get_modelList_user) and tree new models manually. 
// After, it get model list (.../models) and verify status code 
// if that is `status code 200` (success). 

  public function testPutModel()
  {
    /* Prepares the request    */
    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
    Util::addUser("put_model_user", "put_model_user", "put_model_user", "toto", true, true);
        Util::addUser("idrissa", "SOKHONA", "idrissa", "toto", true, true);
    Util::addUser("francisco", "GIMENEZ", "francisco", "toto", true, true);
    /* Prepares the model data */
    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    $data = json_encode(array('name' => "model 1", 'data' => $model_data));
    /* Add a new model (model_1) and verify status code (201 : created) */
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("put_model_user:toto");
    $res = $client->post('http://localhost:8080/server.php/users/put_model_user/models/model_1', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => $data]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode());
    /* Get model for verify if model is created in the server : verify model data */ 
    /* and verify status code (200 : success) */
    $res = $client->get('http://localhost:8080/server.php/users/put_model_user/models/model_1', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);   
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $data = json_decode($res->getBody(), TRUE);
    $this->assertEquals("model 1", $data["name"]);  
    $this->assertEquals($model_data, $data["data"]);  
    /* Update a model (model_1) and verify status code (200 : success) and model data */
    $model_data = '{ x = 33, a = "", y = { 1, 2, 3, 4, 6}}';
    $data = json_encode(array('name' => "model 1", 'data' => $model_data));
    $res = $client->put('http://localhost:8080/server.php/users/put_model_user/models/model_1', 
                        ['headers' => ['Content-Type' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'=='],
                         'body' => $data]);
    $this->assertEquals(STATUS_OK, $res->getStatusCode());
    /* Get model for verify if model is updating in the server : verify model data */ 
    /* and verify status code (200 : success) */
    $res = $client->get('http://localhost:8080/server.php/users/put_model_user/models/model_1', 
                        ['headers' => ['Accept' => 'application/json', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);   
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $data = json_decode($res->getBody(), TRUE);
    $this->assertEquals("model 1", $data["name"]);  
    $this->assertEquals($model_data, $data["data"]);
  }

// ##### Patch a model (patch .../models/:model)
// This is patch a model test. This test start in create 
// a new user (patch_model_user) and tree new models manually. 
// After, it get model list (.../models) and verify status code 
// if that is `status code 200` (success). 
/*
  public function testPatchModel()
  {
    /* Prepares the request    */
/*    $config = Util::getConfig();
    Util::addUserRoot();
    /* Add new user   */
/*    Util::addUser("patch_model_user", "patch_model_user", "patch_model_user", "toto", true, true);
    /* Add a new model manually */
/*    $model_data = '{ x = 1, a = "", y = { 1, 2, 3 }}';
    Util::addModel("patch_model_user", "model_1", $model_data);
    /* Add a new patch (new patch) and verify status code (201 : created) */
/*    $patch_data = 'local p = function (model) model.x = 1 end';
    $client = new GuzzleHttp\Client();
    $encoded = base64_encode("patch_model_user:toto");
    $res = $client->patch('http://localhost:8080/server.php/users/patch_model_user/models/model_1', 
                          ['headers' => ['Content-Type' => 'cosy/model', 
                                         'Authorization' => 'Basic '.$encoded.'=='],
                            //'debug' => true,
                           'body' => $patch_data]);
    $this->assertEquals(STATUS_CREATED, $res->getStatusCode());
    /* Get patch for verify if patch is created in the server : verify patch data */ 
    /* and verify status code (200 : success) */
/*    $res = $client->get('http://localhost:8080/server.php/users/patch_model_user/models/model_1/patches/patch_1', 
                        ['headers' => ['Accept' => 'cosy/model', 
                                       'Authorization' => 'Basic '.$encoded.'==']]);   
    $this->assertEquals(STATUS_OK, $res->getStatusCode()); 
    $data = $res->getBody();
    $this->assertEquals($patch_data, $data); 
  }
*/

// ##### Delete a model (delete .../models/:model)
// This test delete a model in server. This test start
// in create a new user and model, verify if that has been
// created. After, it delete model and 
// get model for verify if the model has been deleted. 
// The server returns `status code 204` (no content) 
// if the model has been deleted. Get model deleted
// return the `status code 410` (Gone).

  public function testDeleteModel()
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
}