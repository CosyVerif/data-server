<?php
/**
 * Constant status
 */
define('STATUS_OK', 200);
define('STATUS_CREATED', 201);

define('STATUS_NO_CONTENT', 204);
define('STATUS_MOVED_PERMANENTLY', 301);
define('STATUS_MOVED_TEMPORARILY', 302);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_UNAUTHORIZED', 401);
define('STATUS_FORBIDDEN', 403);
define('STATUS_NOT_FOUND', 404);
define('STATUS_METHOD_NOT_ALLOWED', 405);
define('STATUS_GONE', 410);
define('STATUS_UNPROCESSABLE_ENTITY', 422);
define('STATUS_INTERNAL_SERVER_ERROR', 500);
define('STATUS_NOT_IMPLEMENTED', 501);


/**
*   Users type
*/


/**
*   Users permissions
*/
define('USER_CREATE', 'user_create'); // All users can use the resource
define('USER_MODIFY', 'user_modify'); // Only user owner and Server administrator can use the resource
define('USER_DELETE', 'user_delete'); // Only user owner and Server administrator can use the resource

/**
*   user permissions of a project
*/
define('CHANGE_PROJECT', 'change_project'); // Project administrator
define('CHANGE_RESOURCE', 'change_resource'); // Permission read (execute if that a service) the project resources

define('IS_PUBLIC', true);

?>