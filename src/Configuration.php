<?php
namespace Nozomi\Core;

use \Slim\PDO\Database;

class Configuration {

  function GetConfig() {
    $configLocation = __DIR__ . '/../../../../site/config.ini';
    $config = parse_ini_file($configLocation, true);
    return $config;
  }

  function ConfigExists() {
    $configLocation = __DIR__ . '/../../../../site/config.ini';
    $config = parse_ini_file($configLocation, true);
    if ($config) return true;
    else return false;
  }

  function put_ini_file($config, $file, $has_section = false, $write_to_file = true){
    $fileContent = '';
    if(!empty($config)){
      foreach($config as $i=>$v){
        if($has_section){
          $fileContent .= "[".$i."]\n\r" . put_ini_file($v, $file, false, false);
        }
        else{
          if(is_array($v)){
            foreach($v as $t=>$m){
              $fileContent .= $i."[".$t."] = ".(is_numeric($m) ? $m : '"'.$m.'"') . "\n\r";
            }
          }
          else $fileContent .= $i . " = " . (is_numeric($v) ? $v : '"'.$v.'"') . "\n\r";
        }
      }
    }

    if($write_to_file && strlen($fileContent)) return file_put_contents($file, $fileContent, LOCK_EX);
    else return $fileContent;
  }

  public function CreateConfiguration($body) {
    $users = "CREATE TABLE `users` (`id` INT(10) NOT NULL AUTO_INCREMENT,`username` VARCHAR(50) NOT NULL,`password` VARCHAR(256) NOT NULL,`role` INT(3) NOT NULL,`join_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB;";

    $admin = "INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, 1);";

    $pages = "CREATE TABLE `pages`( `id` INT(10) NOT NULL AUTO_INCREMENT, `name` VARCHAR(260) NOT NULL, `title` VARCHAR(32) NOT NULL, `author` VARCHAR(50) NOT NULL, `content` TEXT NOT NULL, `template` VARCHAR(50) NOT NULL DEFAULT 'default.html', `last-modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE INDEX `name` (`name`)) COLLATE='utf8_general_ci' ENGINE=InnoDB;";

    $files = "CREATE TABLE `files`( `id` INT(10) NOT NULL, `filename` VARCHAR(64) NOT NULL, `location` VARCHAR(260) NOT NULL, `upload-timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB;";

    $index = "INSERT INTO `pages` (`name`, `title`, `author`, `content`) VALUES ('index', 'Home', 'nozomi', '<h1>Welcome to Nozomi!</h1>');";

    $configLocation = __DIR__ . '/../../../../site/config.ini';

    $bf = new BaseFunc();

    try {
      $s = filter_var($body['sqlh']);
      $d = filter_var($body['sqld']);
      $u = filter_var($body['sqlu']);
      $p = filter_var($body['sqlp']);
      $uu = filter_var($body['user']);
      $options = [
        'cost' => 11,
        'salt' => random_bytes(22)
      ];

      $pass = filter_var($body['pass']);

      $pp = password_hash($pass, PASSWORD_BCRYPT, $options);

      $config = Array (
        'sqlhost' => $s,
        'sqldb' => $d,
        'sqluser' => $u,
        'sqlpass' => $p,
        'theme' => 'default',
        'nozomiuri' => '/nozomi',
        'key' => $bf->random_str(50)
      );

      $conn = new Database("mysql:host=$s;dbname=$d", $u, $p);
      // set the Database error mode to exception
      $conn->setAttribute(Database::ATTR_ERRMODE, Database::ERRMODE_EXCEPTION);

      $conn->exec($users);
      $conn->prepare($admin)->execute([$uu,$pp]);
      $conn->exec($pages);
      $conn->exec($files);
      $conn->exec($index);

      $this->put_ini_file($config, $configLocation);
      return true;
    }
    catch(Exception $e)
    {
      echo "Setup Fail " . $e->getMessage();
      return false;
    }
  }
}
?>