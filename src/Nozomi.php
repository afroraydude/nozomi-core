<?php

namespace Nozomi\Core;

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;
use voku\helper\AntiXSS;

class Nozomi
{
  private $app;

  /**
   * Nozomi constructor.
   * @param \Slim\App $slimApp
   */
  function __construct(\Slim\App $slimApp, NozomiPluginHandler $pluginHandler = null)
  {
    $this->app = $slimApp;
    if (isset($pluginHandler)) {
      $this->registerRoutes($pluginHandler);
    }
    else $this->registerRoutes();
  }

  private function registerRoutes(NozomiPluginHandler $pluginHandler = null)
  {
    $container = $this->app->getContainer();
    $this->app->group('/nozomi', function() {
      $this->get('/assets/{name:.*}', function (Request $request, Response $response, array $args) {
        $path = $args['name'];
        $containingFolder = __DIR__ . '/';
        $filepath = $containingFolder . $path;
        $file = @file_get_contents($filepath);
        $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        $response->write($file);
        $ext = array_pop(explode('.', $filepath));
        if ($ext === 'svg') return $response->withHeader('Content-Type', 'image/svg+xml');
        //if ($ext === 'svg') return $response;
        else return $response->withHeader('Content-Type', $finfo->buffer($file));
      });

      $this->get('/setup', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        if ($conf->ConfigExists() == false) return $this->nozomiRenderer->render($response, 'setup.html');
        else return $response->withRedirect('/nozomi');
      });

      $this->post('/setup', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        if ($conf->ConfigExists() == false) {
          $data = $request->getParsedBody();
          if ($conf->CreateConfiguration($data)) {
            return $response->withRedirect('/');
          } else {
            $this->nozomiRenderer->render($response, 'setup.html');
          }
        } else {
          return $response->withRedirect('/nozomi');
        }
      });

      $this->get('/login', function (Request $request, Response $response, array $args) {
        $this->nozomiRenderer->render($response, 'login.html');
      });

      $this->post('/login', function (Request $request, Response $response, array $args) {
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

      $this->get('', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = Array (
          'pages' => $content->GetPages()
        );
        $this->nozomiRenderer->render($response, 'home.html', $data);
      })->add(new AuthorizationMiddleware(3));

      $this->get('/page/new', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        $config = $conf->GetConfig();
        $templateDir = 'themes/' . $config['theme'];


        $templates = Array();
        foreach (array_filter(glob(__DIR__ . '/../../../../site/' . $templateDir . '/*.html'), 'is_file') as $file) {
          $file = str_replace(__DIR__ . '/../../../../site/' . $templateDir . '/', "", $file);
          array_push($templates, $file);
        }

        $x = Array('templates' => $templates);
        $this->nozomiRenderer->render($response, 'page.html', $x);
      })->add(new AuthorizationMiddleware(2));

      $this->post('/page/post', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = $request->getParsedBody();
        $content->PostPage($data);
      })->add(new AuthorizationMiddleware(2));

      $this->get('/logout', function (Request $request, Response $response, array $args) {
        $_SESSION['token'] = '';
        return $response->withRedirect('/nozomi/login');
      })->add(new AuthorizationMiddleware(3));


      $this->get('/page/getcontent/{name:.*}', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = $content->GetPage($args['name']);
        $antiXss = new AntiXSS();
        $data['content'] = $antiXss->xss_clean($data['content']);
        return $response->withJSON($data);
      })->add(new AuthorizationMiddleware(3))->setName('getcontent');

      $this->get('/page/edit/{name:.*}', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = $content->GetPage($args['name']);

        if ($data) {

          $conf = new Configuration();
          $config = $conf->GetConfig();
          $templateDir = 'themes/' . $config['theme'];
          $templates = Array();
          foreach (array_filter(glob(__DIR__ . '/../../../../site/' . $templateDir . '/*.html'), 'is_file') as $file) {
            $file = str_replace(__DIR__ . '/../../../../site/' . $templateDir . '/', "", $file);
            array_push($templates, $file);
          }
          $data['templates'] = $templates;
          $this->nozomiRenderer->render($response, 'page.html', $data);
        } else {
          return $this->nozomiRenderer->render($response, '404.html');
        }
      })->add(new AuthorizationMiddleware(3))->setName('editpage');
    });

    $this->app->any('/index', function (Request $request, Response $response, array $args) {
      return $response->withRedirect('/', 301);
    });

    $this->app->get('/site/assets/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $conf = new Configuration();
      $config = $conf->GetConfig();
      $containingFolder = __DIR__ . '/../../../../site/themes/' . $config['theme'] . '/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      $finfo = new \Finfo(FILEINFO_MIME_TYPE);
      $response->write($file);
      $ext = array_pop(explode('.', $filepath));
      if ($ext === 'svg') return $response->withHeader('Content-Type', 'image/svg+xml');
      //if ($ext === 'svg') return $response;
      else return $response->withHeader('Content-Type', $finfo->buffer($file));
    });

    if (isset($pluginHandler)) {
      $plugins = $pluginHandler->getPlugins();

      foreach ($plugins as $plugin) {
        $plugin->registerRoutes();
      }
    }

    $this->app->get('/[{name:.*}]', function (Request $request, Response $response, array $args) {
      $conf = new Configuration();
      if ($args) $name = $args['name'];
      else $name = 'index';
      $content = new Content();
      if ($conf->ConfigExists() == false) return $this->nozomiRenderer->render($response, 'installconfirm.html');
      else return $content->RenderPage($response, $this, $name);
    });
  }
}