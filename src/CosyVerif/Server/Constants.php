<?php
/**
 * Constant status
 */
define('STATUS_OK', 200);
define('STATUS_CREATED', 201);

define('STATUS_NO_CONTENT', 204);
define('STATUS_MOVED_PERMANENTLY', 301);
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
define('USER_ADMIN', 1); // Server administrator (root permissions)
define('USER_LIMIT', 2); // authentified users
define('USER_DEFAULT', 3); // not authentified users


/**
*   Resource visibility
*/
define('RESOURCE_PUBLIC', 4); // All users can use the resource
define('RESOURCE_PRIVATE', 5); // Only user owner and Server administrator can use the resource

/**
*   user permissions of a project
*/
define('PROJECT_ADMIN', 6); // Project administrator
define('PROJECT_READ', 7); // Permission read (execute if that a service) the project resources
define('PROJECT_WRITE', 8); // Permission write the project resources

?>