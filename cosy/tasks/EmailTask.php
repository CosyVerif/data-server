<?php

class MailTask extends \Phalcon\CLI\Task
{
  private $transport;
  private $mailer;

  public function __construct ()
  {
    $configuration = $this->getDi () ['configuration']->email;
    $server        = $configuration->server;
    $port          = $configuration->port;
    $security      = $configuration->security;
    $username      = $configuration->username;
    $password      = $configuration->password;
    $this->transport = \Swift_SmtpTransport::newInstance ($server, $port, $security)
      ->setUsername($username)
      ->setPassword($password);
    $this->mailer = \Swift_Mailer::newInstance ($this->transport);
  }

  public function mainAction ()
  {
    $di            = $this->getDI ();
    $queue         = $di ['queue'];
    $configuration = $di ['configuration'];
    $site_name     = $configuration->site->name;
    $site_url      = $configuration->site->url;
    $from_name     = $configuration->email->from_name;
    $from_email    = $configuration->email->from_email;
    while (true)
    {
      $job     = $queue->reserve();
      $message = $job->getBody();
      $job->delete();
      $resource = \Cosy\User::findById ($message ['user-validation']);
      if (! $resource)
        continue;
      $user_name  = $resource->fullname;
      $user_email = $resource->email;
      echo "Sending email to {$user_name} <{$user_email}>...\n";
      $message = \Swift_Message::newInstance()
        ->setSubject ("[{$site_name}] Account validation")
        ->setTo   ([$resource->email  => $resource->fullname])
        ->setFrom ([$from_email       => $from_name         ])
        ->setBody ("{$site_url}/check/{$resource->validation_key}");
      $this->mailer->send ($message);
    }
  }
}
