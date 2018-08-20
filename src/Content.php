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

    // TODO: Render upldate timestamp

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

  function getPages() {
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

  function postUser($data) {
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
    $auth = new Authorization();
    $userRole = $auth->get_role($data['username']);
    $pass = $data['password'];

    $pp = password_hash($pass, PASSWORD_BCRYPT, $options);
    if ($userRole) {
      $conn->prepare("INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, 1);")->execute([$data['username'], $pp, $data['role']]);
    }
  }

  function renderError($rs, $ex) {
    $data = Array (
      'error' => $ex
    );

    $error = '<!DOCTYPE html><html lang="en"><head> <meta charset="UTF-8"> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script> <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script> <link rel="stylesheet" href="/nozomi/assets/css/bootstrap.min.css"> <link rel="stylesheet" href="/nozomi/assets/css/nozomi.css"> <script src="/nozomi/assets/js/bootstrap.min.js"></script> <link rel="apple-touch-icon" sizes="180x180" href="/nozomi/assets/favicons/apple-touch-icon.png?v=wishstarredyellow"> <link rel="icon" type="image/png" sizes="32x32" href="/nozomi/assets/favicons/favicon-32x32.png?v=wishstarredyellow"> <link rel="icon" type="image/png" sizes="16x16" href="/nozomi/assets/favicons/favicon-16x16.png?v=wishstarredyellow"> <link rel="icon" type="image/ico" sizes="16x16" href="/nozomi/assets/favicons/favicon-16x16.ico?v=wishstarredyellow"> <link rel="manifest" href="/nozomi/assets/favicons/site.webmanifest?v=wishstarredyellow"> <link rel="mask-icon" href="/nozomi/assets/favicons/safari-pinned-tab.svg?v=wishstarredyellow" color="#5bbad5"> <link rel="shortcut icon" href="/nozomi/assets/favicons/favicon.ico?v=wishstarredyellow"> <meta name="apple-mobile-web-app-title" content="Nozomi"> <meta name="application-name" content="Nozomi"> <meta name="msapplication-TileColor" content="#ffffff"> <meta name="theme-color" content="#ffffff"></head><body><div class="container">';
    $error .= $ex;
    $error .= '</p></div></body></html>';
    return $rs->withStatus(500)
      ->withHeader('Content-Type', 'text/html')
      ->write($error);
  }

  function getUsers() {
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
    return $conn->query("SELECT `id`,`username`,`role` FROM `users`")->fetchAll();
  }
}