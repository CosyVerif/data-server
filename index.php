<?php

require __DIR__ . '/vendor/autoload.php';

$ini = parse_ini_file ('cosy.ini', true);
$data = new \Cosy\Data ($ini);
$d = $data->resource ('http://127.0.0.1/users/user');

$d->username = "user 1";
var_dump ($d->username);
$data->delete ($d);
var_dump ($d);
