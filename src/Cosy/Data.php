<?php
namespace Cosy;

class Data
{
  public $cosy;
  public $redis;

  public function __construct ($configuration)
  {
    // Connect to redis server:
    $redis_configuration = $configuration ['redis'];
    assert (is_array ($redis_configuration));
    $this->redis = new \Predis\Client ($redis_configuration);
    assert ($this->redis);
    // Extract base URL for resources:
    $base_url = $configuration ['cosy'] ['url'];
    assert (is_string ($base_url));
    $this->cosy = parse_url ($base_url);
  }

  public function resource ($url)
  {
    $result = array ();
    $parsed = parse_url ($url);
    assert ($parsed ['host'] == $this->cosy ['host']);
    $parts  = explode ($parsed ['path'], '/');
    // build one resource for each part of path
    return new Data\User ($this, $url);
  }

  public function delete ($resource)
  {
    Data\Hash::delete ($resource);
  }

}


/*
 * hash for /
 * public:    boolean
 *
 * hash for /users/user:
 * username:  identifier
 * password:  string
 * public:    boolean
 * is_admin:  boolean
 * active:    boolean
 * validation_key: string
 * share_read:  key of set
 * share_write: key of set
 *
 * hash for /users/user/rs/resource:
 * identifier:  identifier
 * name:        string
 * description: string
 * public:      boolean
 * share_read:  key of set
 * share_write: key of set
 *
 */
