<?php
namespace Nozomi\Core;

class Content {
  function RenderPage($rs, $app, $page) {
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
    $conn = new PDO("mysql:host=$s;dbname=$d", $u, $p);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM pages WHERE `name` = ? LIMIT 1");
    $stmt->execute([$page]);

    $x = $stmt->fetch();

    if($x) {
      return $x;
    } else {
      return '';
    }
  }
}