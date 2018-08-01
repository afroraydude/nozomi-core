<?php

namespace Nozomi\Core;

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

class Nozomi
{
  private $app;

  function __construct($slimApp)
  {
    $this->app = $slimApp;
    $this->registerRoutes($this->app);
  }
//https://stackoverflow.com/questions/39559180/serving-dynamic-assets-with-slim-and-twig
//https://www.slimframework.com/docs/v3/objects/router.html#how-to-create-routes

  private function registerRoutes($app)
  {
    $container = $app->getContainer();

    $app->get('/nozomi/assets/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $containingFolder = __DIR__ . '/../nozomi/data/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      print($file);
      $finfo = new \Finfo(FILEINFO_MIME_TYPE);
      $response->write($file);
      return $response->withHeader('Content-Type', $finfo->buffer($file));
    });

    $app->get('/site/assets/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $conf = new Configuration();
      $config = $conf->GetConfig();
      $containingFolder = __DIR__ . '/../site/themes/' . $config['theme'] . '/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      print($file);
      $finfo = new \Finfo(FILEINFO_MIME_TYPE);
      $response->write($file);
      return $response->withHeader('Content-Type', $finfo->buffer($file));
    });

    $app->get('/nozomi/setup', function (Request $request, Response $response, array $args) {
      $conf = new Configuration();
      if ($conf->ConfigExists() == false) return $this->nozomiRenderer->render($response, 'setup.html');
      else return $response->withRedirect('/');
    });

    $app->post('/nozomi/setup', function (Request $request, Response $response, array $args) {
      $conf = new Configuration();
      if ($conf->ConfigExists() == false) {
        $data = $request->getParsedBody();
        if ($conf->CreateConfiguration($data)) {
          return $response->withRedirect('/');
        } else {
          $this->nozomiRenderer->render($response, 'setup.html');
        }
      } else {
        return $response->withRedirect('/');
      }
    });

    $app->get('/nozomi/login', function (Request $request, Response $response, array $args) {
      $this->nozomiRenderer->render($response, 'login.html');
    });

    $app->post('/nozomi/login', function (Request $request, Response $response, array $args) {
      $conf = new Configuration();
      $config = $conf->GetConfig();

      $data = $request->getParsedBody();
      $user = $data['username'];
      $pass = $data['password'];

      $auth = new Authorization();
      if ($auth->verify_password($user, $pass)) {
        $key = $config['key'];
        $token = array(
          'user' => $user
        );
        $jwt = JWT::encode($token, $key);
        $_SESSION['token'] = $jwt;
        return $response->withRedirect('/nozomi');
      } else {
        return $response->withRedirect('/nozomi/login');
      }
    });

    $app->get('/nozomi', function (Request $request, Response $response, array $args) {
      $this->nozomiRenderer->render($response, 'home.html');
    })->add(new AuthorizationMiddleware(3));

    $app->get('/nozomi/page/new', function (Request $request, Response $response, array $args) {
      $conf = new Configuration();
      $config = $conf->GetConfig();
      $templateDir = 'themes/' . $config['theme'];


      $templates = Array();
      foreach (array_filter(glob(__DIR__ . '/../site/' . $templateDir . '/*.html'), 'is_file') as $file) {
        $file = str_replace(__DIR__ . '/../site/' . $templateDir . '/', "", $file);
        array_push($templates, $file);
      }

      $x = Array('templates' => $templates);
      $this->nozomiRenderer->render($response, 'page.html', $x);
    })->add(new AuthorizationMiddleware(2));

    $app->post('/nozomi/page/post', function (Request $request, Response $response, array $args) {
      $data = $request->getParsedBody();
      return $response->withJson($data);
    })->add(new AuthorizationMiddleware(2));

    $app->get('/nozomi/logout', function (Request $request, Response $response, array $args) {
      $_SESSION['token'] = '';
      return $response->withRedirect('/nozomi/login');
    })->add(new AuthorizationMiddleware(3));

    $app->get('/nozomi/page/edit/{name:.*}', function (Request $request, Response $response, array $args) {
      $content = new Content();
      $data = $content->GetPage($args['name']);

      if ($data) {

        $conf = new Configuration();
        $config = $conf->GetConfig();
        $templateDir = 'themes/' . $config['theme'];
        $templates = Array();
        foreach (array_filter(glob(__DIR__ . '/../site/' . $templateDir . '/*.html'), 'is_file') as $file) {
          $file = str_replace(__DIR__ . '/../site/' . $templateDir . '/', "", $file);
          array_push($templates, $file);
        }
        $data['templates'] = $templates;
        $this->nozomiRenderer->render($response, 'page.html', $data);
      } else {
        return $this->nozomiRenderer->render($response, '404.html');
      }
    })->add(new AuthorizationMiddleware(3));
  }
}