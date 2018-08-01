<?php

namespace Nozomi\Core;

use \Firebase\JWT\JWT;

class Authorization {
  function verify_password($username, $password) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new PDO("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $x = $conn->query("SELECT `username`,`password` FROM `users` WHERE `username` = '$username' LIMIT 1")->fetch();
    if (password_verify($password, $x['password'])) {
      return true;
    }
    else {
      return false;
    }
  }

  function auth($token, $reqRole) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    $key = $config['key'];
    if($token) {
      try {
        $decoded = JWT::decode($token, $key, array('HS256'));
        $decoded_array = (array)$decoded;
        $role = $this->get_role($decoded_array['user']);
        if ($role <= $reqRole) return true;
        else return false;
      } catch(Exception $e) {
        return false;
      }

    }  else
    {
      return false;
    }
  }

  function get_role($user) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new PDO("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $x = $conn->query("SELECT `username`,`role` FROM `users` WHERE `username` = '$user' LIMIT 1")->fetch();
    return $x['role'];
  }
}