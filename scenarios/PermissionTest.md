-- # User Permission Test 
--
-- The test will cover user permission test. many cases (case 
-- sucess or cases errors) can have present itself to us : 
--
-- * The user has all permissions,
-- * The requested resource is its,
-- * The user actions are limited and it has permission
-- * The user actions are limited and it did not have permission,
-- * The resource is not found or the action is not implemented.
--
--
--
-- ##### Administrator
--
-- This test treats a user has all permissions (we can have multiple
-- administrators). If a user is administrator, it will be served.
--
--
-- 
-- ##### Its resource
--
-- This test treats the use of its resources. The requested resource
-- is its, so it will be served.
--
--
--
-- ##### Use limited and actions permitted
--
-- This test treats the case where the user actions are limited and
-- it has the permission. It has the permission because the resource
-- is a public resource (it's not a project resource) or the 
-- resource is a project resource and the user participates and it
-- has the permission on action.
--
--
--
-- ##### Use limited and actions not permitted
--
-- This test treats the case where the user actions are limited and
-- it doesn't have the permission. It doesn't have the permission
-- because the resource is not a public resource or user does not
-- participates in the project or it participates but it does not 
-- have the permission of the action.
--
--
--
-- ##### The resource is not found or the action is not implemented
--
-- This case, it is allowed to pass. In any case the action is not 
-- satisfied.
 
















