<?php

// http://www.sitepoint.com/sending-confirmation-emails-phalcon-swift/
final class Mail extends Phalcon\Mvc\User\Component
{
  private $from_name  = 'Cosy.io Accounts';
  private $from_email = 'accounts@cosy.io';
  private $server     = 'smtp.gmail.com';
  private $port       = 465;
  private $security   = 'ssl';
  private $username   = 'test@gmail.com';
  private $password   = 'test';

  private $transport;
  private $mailer;

  public function __construct ()
  {
    $this->transport = Swift_SmtpTransport::newInstance (
      $this->server,
      $this->port,
      $this->security
    )
      ->setUsername($this->username)
      ->setPassword($this->password);
    $this->mailer = Swift_Mailer::newInstance ($this->transport);
  }

  public function send ($to, $name, $validation_key)
  {
    $message = Swift_Message::newInstance()
      ->setSubject ('[Cosy] Account validation')
      ->setTo ([$to => $name])
      ->setFrom([$this->from_email => $this->from_name])
      ->setBody("Validate using url: {$this->validation_key}.");
    return $this->mailer->send ($message);
  }
}
