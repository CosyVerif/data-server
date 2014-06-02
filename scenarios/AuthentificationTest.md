-- # User Authentication Test 
--
-- The test will cover user authentication test. many cases (case 
-- sucess or cases errors) can have present itself to us : 
--
-- * Accept the authentication of a user,
-- * Not accept the authentication,
-- * Authentication information is not provided,
-- * internal server error
-- ...
--
--
--
-- ##### Accept the authentication of a user
--
-- This test accept the authentication of a valid user. The client requests
-- the server with a valid `username` and encrypted `password'. The server
-- authentificate the user and satisfied the request. The password is 
-- encoded into base64.
--
--
-- 
-- ##### Not accept the authentication
--
-- The request of the test does not pass because username and/or password
-- are incorrects. So, the server does not satisfied the request and 
-- return `status code 401` (Unauthorized) and it add `WWW-Authenticate`
-- header in the response.
--
--
--
-- ##### Authentification information is not provided
--
-- The request of the test does not pass because `username` and `password`
-- are not proveded. So, the server considers the user as public user.
-- He has not permission so the request satisfied if the request resource
-- that is public resource.
 
















