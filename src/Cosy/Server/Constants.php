<?php

namespace CosyVerif\Server;

class Constants
{
  public static function register()
  {
    define('STATUS_OK', 200);
    define('STATUS_CREATED', 201);

    define('STATUS_NO_CONTENT', 204);
    define('STATUS_MOVED_PERMANENTLY', 301);
    define('STATUS_MOVED_TEMPORARILY', 302);
    define('STATUS_PERMANENT_REDIRECT', 308);
    define('STATUS_BAD_REQUEST', 400);
    define('STATUS_UNAUTHORIZED', 401);
    define('STATUS_FORBIDDEN', 403);
    define('STATUS_NOT_FOUND', 404);
    define('STATUS_METHOD_NOT_ALLOWED', 405);
    define('STATUS_CONFLICT', 409);
    define('STATUS_GONE', 410);
    define('STATUS_UNPROCESSABLE_ENTITY', 422);
    define('STATUS_INTERNAL_SERVER_ERROR', 500);
    define('STATUS_NOT_IMPLEMENTED', 501);

    define('ADMIN_USER', 'admin_user');
    define('ADMIN_PROJECT', 'admin_project');
    define('EDIT_PROJECT', 'edit_project');

    define('IS_PUBLIC', true);

    define('STATUS_SEND', 'send');
    define('STATUS_RECEIVED', 'received');
    define('STATUS_ACK', 'ack');
    define('STATUS_COMMENT', 'comment');
    define('STATUS_DENIED', 'denied');
    define('STATUS_CANCELED', 'canceled');
    define('STATUS_VIEW', 'view');
  }
}
