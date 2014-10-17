<?php
namespace Cosy\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Email extends Command
{
  protected function configure ()
  {
    $this
      ->setName('server:email')
      ->setDescription('Run the email sending server.')
    ;
  }

  protected function execute (InputInterface $input, OutputInterface $output)
  {
    $task = new \Cosy\Task\EmailTask;
    $task->mainAction ();
  }
}
