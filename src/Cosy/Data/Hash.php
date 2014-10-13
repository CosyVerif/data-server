<?php
namespace Cosy\Data;

abstract class Hash
{
  const CLASS_NAME = '\Cosy\Data\Hash';

  private $library;
  private $resource;
  private $is_set = false;
  private $class;

  public function __construct (\Cosy\Data $library, $resource)
  {
    $this->library  = $library;
    $this->resource = $resource;
    $this->class    = get_class ($this);
    // Check that all properties in the class are "protected":
    $reflect = new \ReflectionObject ($this);
    foreach ($reflect->getProperties () as $property)
      if (! property_exists (Hash::CLASS_NAME, $property))
        if (! $property->isProtected ())
          throw new \InvalidArgumentException ("Property '${key}' must be 'protected'.");
  }

  public static function update ($that)
  {
    $reader = \Minime\Annotations\Reader::createFromDefaults();
    $values = $that->library->redis->hgetall ($that->resource);
    foreach ($values as $k => $v)
    {
      assert (! property_exists (Hash::CLASS_NAME, $k) && property_exists ($that, $k));
      $type = $reader->getPropertyAnnotations ($that->class, $k)->get ('var');
      // FIXME: switch behavior depending on type.
      // * to_redis (+ check)
      // * from_redis (- check)
      // * class (handler class, for instance Hash)
      $that->$k = $v;
    }
    $that->is_set = true;
  }

  public static function delete ($that)
  {
    $that->library->redis->del ($that->resource);
    $that->is_set = false;
    unset ($that);
  }

  public static function unset ($that)
  {
    $that->is_set = false;
  }

  public function __get ($key)
  {
    if (property_exists (Hash::CLASS_NAME, $key))
      return $this->$key;
    if (! $this->is_set)
      Hash::update ($this);
    if (! property_exists ($this, $key))
      throw new \InvalidArgumentException ("Unknown property '${key}'.");
    return $this->$key;
  }

  public function __set ($key, $value)
  {
    if (property_exists (Hash::CLASS_NAME, $key))
      throw new \InvalidArgumentException ("Read-only property '${key}'.");
    if (! property_exists ($this, $key))
      throw new \InvalidArgumentException ("Unknown property '${key}'.");
    $this->library->redis->hset ($this->resource, $key, $value);
    $this->$key = $value;
  }

  public function __issset ($key)
  {
    if (property_exists (Hash::CLASS_NAME, $key))
      throw new \InvalidArgumentException ("Read-only property '${key}'.");
    if (! $this->is_set)
      Hash::update ($this);
    return property_exists ($this, $key);
  }

  public function __unset ($key)
  {
    if (property_exists (Hash::CLASS_NAME, $key))
      throw new \InvalidArgumentException ("Read-only property '${key}'.");
    if (! property_exists ($this, $key))
      throw new \InvalidArgumentException ("Unknown property '${key}'.");
    $this->library->redis->hdel ($this->resource, $key);
    unset ($this->$key);
  }
}
