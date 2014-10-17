<?php
namespace Cosy\Task;

class EmailTask extends \Phalcon\CLI\Task
{
  public function mainAction ()
  {
    $di            = $this->getDI ();
    $queue         = $di ['queue'];
    $configuration = $di ['configuration'];
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
      // Create and send email:
      $mail             = new \PHPMailer;
      $mail->isSMTP ();
      $mail->Host       = $configuration->email->host;
      $mail->Port       = $configuration->email->port;
      $mail->SMTPAuth   = true;
      $mail->Username   = $configuration->email->username;
      $mail->Password   = $configuration->email->password;
      $mail->SMTPSecure = $configuration->email->security;
      $mail->From       = $configuration->email->from_email;
      $mail->FromName   = $configuration->email->from_name;
      $mail->WordWrap   = 76;
      $mail->Subject    = "[{$configuration->site->name}] Account validation";
      $mail->Body       = "{$configuration->site->url}/check/{$resource->validation_key}";
      $mail->addAddress($user_email, $user_name);
      $mail->send ();
    }
  }
}
