<?php

namespace Cosy\Data;

use \Phalcon\Mvc\Model\Message as Message;

abstract class Base extends \Phalcon\Mvc\Collection
{
  public $resource;

  public function getResource ()
  {
    return $this->resource;
  }

  public function getSource ()
  {
    return end (explode ('\\', get_class ($this)));
  }

  protected final function beforeValidation ()
  {
    $this->setId ($this->resource);
  }

  protected final function initialize()
  {
    $this->useImplicitObjectIds(false);
  }

  protected final function afterSave ()
  {
    // TODO: publish change
  }

  protected final function afterValidationOnCreate ()
  {
    $class = get_class ($this);
    // If an object with the same Identifier already exists,
    // there is an error:
    $found = $class::findById ($this->resource);
    if ($found)
    {
      $this->appendMessage (new Message ("Resource {$this->resource} already exists."));
      return false;
    }
    // If it is a redirect, we suppress it:
    $found = Redirect::findById ($this->resource);
    if ($found)
      $existing->delete ();
  }

  protected final function afterValidationOnUpdate ()
  {
    // TODO
  }

  protected abstract function validation ();
}
