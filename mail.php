<?php

require_once __DIR__ . '/cosy.php';

$queue = $di ['queue'];
$email = $di ['email'];

while (true) {
  $job     = $queue->reserve();
  $message = $job->getBody();
  var_dump($message);
  $job->delete();
  $resource = \Cosy\Data\User::findById ($message ['user-validation']);
  if (! $resource)
    continue;
  $user_name  = $resource->fullname;
  $user_email = $resource->email;
  echo "Sending email to {$user_name} <{$user_email}>...\n";
  $email->send ($resource);
}
