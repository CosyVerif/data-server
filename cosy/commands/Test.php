<?php
namespace Cosy\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{
  protected function configure ()
  {
    $this
      ->setName('test')
      ->setDescription('Run some tests.')
    ;
  }

  protected function execute (InputInterface $input, OutputInterface $output)
  {
    $task = new \Cosy\Task\TestTask;
    $task->mainAction ();
  }
}
