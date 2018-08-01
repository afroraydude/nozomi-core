<?php
namespace Nozomi\Core;
use \Slim\PDO\Database;

class Content {
  function RenderPage($rs, $app, $page) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new Database("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(Database::ATTR_ERRMODE, Database::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT `template`,`content`,`title` FROM pages WHERE `name` = ? LIMIT 1");
    $stmt->execute([$page]);

    $x = $stmt->fetch();

    if($x) {
      $templateDir = 'themes/'.$config['theme'];
      $template = $templateDir.'/'.$x['template'];

      return $app->siteRenderer->render($rs, $template, $x);
    } else {
      return $app->nozomiRenderer->render($rs, '404.html');
    }
  }

  function GetPage($page) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new \Slim\PDO\Database("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(Database::ATTR_ERRMODE, Database::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM pages WHERE `name` = ? LIMIT 1");
    $stmt->execute([$page]);

    $x = $stmt->fetch();

    if($x) {
      return $x;
    } else {
      return '';
    }
  }

  function PostPage($data) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new Database("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(Database::ATTR_ERRMODE, Database::ERRMODE_EXCEPTION);
    $page = $this->GetPage($data['original']);
    if (!$page) {
      $conn->prepare("INSERT INTO `pages` (`name`, `title`, `author`, `content`, `template`) VALUES (?, ?, ?, ?, ?);")->execute([$data['url'],$data['title'],'nozomi',$data['content'],$data['template']]);
    } else {
      $conn->prepare("UPDATE `pages` SET `name`=?, `title`=?, `author`=?, `content`=?, `template`=? WHERE `name`=?;")->execute([$data['url'],$data['title'],'nozomi',$data['content'],$data['template'],$data['original']]);
    }
  }

  function GetPages() {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    //echo json_encode($config);
    $s = $config['sqlhost'];
    $d = $config['sqldb'];
    $u = $config['sqluser'];
    $p = $config['sqlpass'];
    $conn = new Database("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(Database::ATTR_ERRMODE, Database::ERRMODE_EXCEPTION);
    return $conn->query("SELECT `id`,`title`,`name` FROM `pages`")->fetchAll();
  }
}