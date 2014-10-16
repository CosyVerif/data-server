<?php

class TestTask extends \Phalcon\CLI\Task
{
  public function mainAction ()
  {
    $di   = $this->getDI ();
    $conf = $di ['configuration'];
    $cosy = \Cosy::instantiate (['resource' => 'http://cosy.io']);
    $user = $cosy->createUser ([
      'username' => 'alban',
      'password' => 'toto',
      'email'    => 'alban.linard@lsv.ens-cachan.fr',
      'fullname' => 'Alban Linard',
    ]);
  }
}
