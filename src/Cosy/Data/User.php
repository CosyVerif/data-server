<?php
namespace Cosy\Data;

final class User extends Hash
{
  /** @var identifier */
  protected $username;
  /** @var string */
  protected $password;

  /** @var bool */
  protected $is_admin;
  /** @var bool */
  protected $is_active;

  /** @var string */
  protected $validation_key;

  /** @var bool */
  protected $is_public;
  /** @var set */
  protected $share_read;
  /** @var set */
  protected $share_write;
}
