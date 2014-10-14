<?php

namespace Cosy\Data;

abstract class Base extends \Phalcon\Mvc\Collection
{
  public function getSource ()
  {
    return end (explode ('\\', get_class ($this)));
  }

  protected function beforeValidation ()
  {
    if (! filter_var ($this->resource, FILTER_VALIDATE_URL))
      return false;
    $this->_id = $this->resource;
  }

  protected function initialize()
  {
    $this->useImplicitObjectIds(false);
  }

  protected function afterSave ()
  {
    // TODO: publish change
  }

  protected abstract function validation ();
}
