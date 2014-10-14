<?php

namespace Cosy\Data;

final class Redirect extends Base
{
  public $resource    = "http://cosy.io/";
  public $redirection;

  protected function validation ()
  {
    return filter_var ($this->resource    , FILTER_VALIDATE_URL)
        && filter_var ($this->redirection , FILTER_VALIDATE_URL)
         ;
  }
}
