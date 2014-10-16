<?php
namespace Cosy;

// http://www.sitepoint.com/sending-confirmation-emails-phalcon-swift/
final class Mail extends \Phalcon\Mvc\User\Component
{
  private $transport;
  private $mailer;

  public function __construct ()
  {
    $configuration = $this->getDi () ['configuration']->email;
    $server     = $configuration->server;
    $port       = $configuration->port;
    $security   = $configuration->security;
    $username   = $configuration->username;
    $password   = $configuration->password;
    $this->transport = \Swift_SmtpTransport::newInstance ($server, $port, $security)
      ->setUsername($username)
      ->setPassword($password);
    $this->mailer = \Swift_Mailer::newInstance ($this->transport);
  }

  public function send ($resource)
  {
    $configuration = $this->getDi () ['configuration'];
    $site_name  = $configuration->site->name;
    $site_url   = $configuration->site->url;
    $from_name  = $configuration->email->from_name;
    $from_email = $configuration->email->from_email;
    $message = \Swift_Message::newInstance()
      ->setSubject ("[{$site_name}] Account validation")
      ->setTo   ([$resource->email  => $resource->fullname])
      ->setFrom ([$from_email       => $from_name         ])
      ->setBody ("{$site_url}/check/{$resource->validation_key}");
    return $this->mailer->send ($message);
  }
}
