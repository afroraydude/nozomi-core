<?php

namespace Nozomi\Core;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \Firebase\JWT\JWT;
use \voku\helper\AntiXSS;
use \Dflydev\FigCookies\FigResponseCookies;
use \Composer\Console\Application;
use \Composer\Command\UpdateCommand;
use \Symfony\Component\Console\Input\ArrayInput;

class Nozomi
{
  private $app;

  private $sidebars = [];

  /**
   * Nozomi constructor.
   * @param \Slim\App $slimApp
   */
  function __construct(\Slim\App $slimApp, NozomiPluginHandler $pluginHandler = null)
  {
    $this->app = $slimApp;
    if (isset($pluginHandler)) {
      $this->registerRoutes($pluginHandler);
    } else $this->registerRoutes();
  }

  private function settings($container)
  {
    // DIC

    $container['logger'] = function ($c) {
      $settings = $c->get('settings')['logger'];
      $logger = new Monolog\Logger($settings['name']);
      $logger->pushProcessor(new Monolog\Processor\UidProcessor());
      $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
      return $logger;
    };

    $container['nozomiRenderer'] = function ($container) {
      $settings = $container->get('settings')['nozomi'];
      $array = Array();
      $view = new \Slim\Views\Twig(__DIR__ . '/templates', [
        // 'cache' => __DIR__ . '/cache'
      ]);

      // Instantiate and add Slim specific extension
      $url = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getPath()), '/');

      $view->addExtension(new \Slim\Views\TwigExtension($container->get('router'), $url));
      return $view;
    };

    // TODO: Fix settings import break
    $container['siteRenderer'] = function ($container) {
      $settings = $container->get('settings')['nozomi'];
      $view = new \Slim\Views\Twig(__DIR__ . '/../../../../site', [
        'cache' => false
      ]);

      // Instantiate and add Slim specific extension
      $url = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getPath()), '/');
      $view->addExtension(new \Slim\Views\TwigExtension($container->get('router'), $url));
      return $view;
    };

    $container['upload_directory'] = function ($container) {
      $settings = $container->get('settings')['nozomi'];
      return __DIR__ . '/../../../../site/files';
    };

    $container['errorHandler'] = function ($container) {
      return function ($request, $response, $exception) use ($container) {
        $content = new Content();
        return $content->renderError($response, $exception);
      };
    };
  }

  private function registerRoutes(NozomiPluginHandler $pluginHandler = null)
  {
    $this->app->add(new NozomiMiddleware());

    if ($pluginHandler) {
      $this->sidebars = $pluginHandler->getSidebars();
    }

    $container = $this->app->getContainer();

    $settings = $container->get('settings');
    $settings->replace([
      'nozomi' => [
        'pages_path' => __DIR__ . '/templates',
        'site_path' => __DIR__ . '/../../../../site',
        'cache_path' => false,
        'data_path' => __DIR__ . '../nozomi/data',
        'site_files_paths' => __DIR__ . '/../../../../site/files'
      ],
      'logger' => [
        'name' => 'nozomi_site',
        'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'level' => \Monolog\Logger::DEBUG,
      ]
    ]);

    $this->settings($container);

    $conf = new Configuration();
    $config = $conf->GetConfig();

    $nozomi = $this;

    $this->app->group('/nozomi', function () use ($nozomi) {

      // TODO: NAME ROUTES AND USE PATH_FOR INSTEAD OF SENDING NOZOMIURL

      $this->get('/setup', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        $config = $conf->GetConfig();
        if ($conf->ConfigExists() == false) return $this->nozomiRenderer->render($response, 'setup.html');
        else return $response->withRedirect($config['nozomiurl']);
      });

      $this->post('/setup', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        $config = $conf->GetConfig();
        if ($conf->ConfigExists() == false) {
          $data = $request->getParsedBody();
          if ($conf->CreateConfiguration($data)) {
            return $response->withRedirect('/');
          } else {
            $this->nozomiRenderer->render($response, 'setup.html');
          }
        } else {
          return $response->withRedirect($config['nozomiurl']);
        }
      });

      $this->get('/login', function (Request $request, Response $response, array $args) {
        $conf = new Configuration();
        $config = $conf->GetConfig();
        $array = Array(
          "nozomiurl" => $config['nozomiurl']
        );
        $this->nozomiRenderer->render($response, 'login.html', $array);
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

          return $response->withRedirect($config['nozomiurl']);
        } else {
          return $response->withRedirect($config['nozomiurl'] . '/login');
        }
      });

      $this->get('[/]', function (Request $request, Response $response, array $args) use ($nozomi) {
        $content = new Content();
        $data = Array(
          'pages' => sizeof($content->GetPages()),
          'sidebars' => $nozomi->sidebars
        );
        $this->nozomiRenderer->render($response, 'home.html', $data);
      })->add(new AuthorizationMiddleware(4));

      $this->get('/page/new', function (Request $request, Response $response, array $args) use ($nozomi) {
        $conf = new Configuration();
        $config = $conf->GetConfig();
        $templateDir = 'themes/' . $config['theme'];
        $templates = Array();
        foreach (array_filter(glob(__DIR__ . '/../../../../site/' . $templateDir . '/*.html'), 'is_file') as $file) {
          $file = str_replace(__DIR__ . '/../../../../site/' . $templateDir . '/', "", $file);
          array_push($templates, $file);
        }

        $x = Array(
          'templates' => $templates,
          'sidebars' => $nozomi->sidebars
        );
        $this->nozomiRenderer->render($response, 'page.html', $x);
      })->add(new AuthorizationMiddleware(3));

      $this->post('/page/post', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = $request->getParsedBody();
        $content->PostPage($data);
      })->add(new AuthorizationMiddleware(3))->setName('nozomipagepost');

      $this->get('/logout', function (Request $request, Response $response, array $args) {
        $_SESSION['token'] = '';
        return $response->withRedirect('/');
      })->add(new AuthorizationMiddleware(4));


      $this->get('/page/getcontent/{name:.*}', function (Request $request, Response $response, array $args) {
        $content = new Content();
        $data = $content->GetPage($args['name']);
        $antiXss = new AntiXSS();
        $antiXss = $antiXss->removeEvilAttributes(['style']);
        $antiXss = $antiXss->removeEvilAttributes(['src']);
        $data['content'] = $antiXss->xss_clean($data['content']);
        return $response->withJSON($data);
      })->add(new AuthorizationMiddleware(4))->setName('nozomigetcontent');

      $this->get('/page/edit/{name:.*}', function (Request $request, Response $response, array $args) use ($nozomi) {
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
          $data['sidebars'] = $nozomi->sidebars;
          $this->nozomiRenderer->render($response, 'page.html', $data);
        } else {
          return $this->nozomiRenderer->render($response, '404.html');
        }
      })->add(new AuthorizationMiddleware(4))->setName('editpage');

      $this->get('/user/new', function (Request $request, Response $response, array $args) use ($nozomi) {
        $data = Array(
          'sidebars' => $nozomi->sidebars
        );
        return $this->nozomiRenderer->render($response, 'user.html', $data);

      })->add(new AuthorizationMiddleware(2));

      $this->get('/file/new', function (Request $request, Response $response, array $args) use ($nozomi) {
        $data = Array(
          'sidebars' => $nozomi->sidebars
        );

        return $this->nozomiRenderer->render($response, 'file.html', $data);
      })->add(new AuthorizationMiddleware(3));

      $this->get('/settings', function (Request $request, Response $response, array $args) use ($nozomi) {
        $config = new Configuration();
        $config = $config->GetConfig();
        $data = Array(
          'sidebars' => $nozomi->sidebars,
          'config' => $config
        );

        return $this->nozomiRenderer->render($response, 'settings.html', $data);
      })->add(new AuthorizationMiddleware(2));

      $this->get('/page/view', function (Request $request, Response $response, array $args) use ($nozomi) {
        $content = new Content();
        $data = Array(
          'pages' => $content->GetPages(),
          'sidebars' => $nozomi->sidebars
        );

        return $this->nozomiRenderer->render($response, 'pages.html', $data);
      })->add(new AuthorizationMiddleware(4));

      $this->post('/file/post', function (Request $request, Response $response, array $args) {
        $directory = $this->get('upload_directory');

        $uploadedFiles = $request->getUploadedFiles();

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['example1'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
          $basefunc = new BaseFunc();
          $filename = $basefunc->moveUploadedFile($directory, $uploadedFile);
          $response->write('uploaded ' . $filename . '<br/>');
        }
      })->add(new AuthorizationMiddleware(3));

      $this->get('/update', function (Request $request, Response $response, array $args) use ($nozomi) {
        //Use the Composer classes


// change out of the webroot so that the vendors file is not created in
// a place that will be visible to the intahwebz
        chdir('../');

//Create the commands
        $input = new ArrayInput(array('command' => 'update'));

//Create the application and run it with the commands
        $application = new Application();
        $application->run($input);

      })->add(new AuthorizationMiddleware(2));
    });

    $this->app->any('/index', function (Request $request, Response $response, array $args) {
      return $response->withRedirect('/', 301);
    });

    $this->app->get('/nozomi/assets/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $containingFolder = __DIR__ . '/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      if ($file) {
        $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        $response->write($file);
        $explosion = explode('.', $filepath);
        $ext = array_pop($explosion);
        if ($ext === 'svg') return $response->withHeader('Content-Type', 'image/svg+xml');
        //if ($ext === 'svg') return $response;
        else return $response->withHeader('Content-Type', $finfo->buffer($file));
      } else {
        $content = new Content();
        return $content->RenderPage($response, $this, '404');
      }
    });

    $this->app->get('/site/assets/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $conf = new Configuration();
      $config = $conf->GetConfig();
      $containingFolder = __DIR__ . '/../../../../site/themes/' . $config['theme'] . '/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      if ($file) {
        $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        $response->write($file);
        $explosion = explode('.', $filepath);
        $ext = array_pop($explosion);
        if ($ext === 'svg') return $response->withHeader('Content-Type', 'image/svg+xml');
        //if ($ext === 'svg') return $response;
        else if ($ext === 'php') return;
        else return $response->withHeader('Content-Type', $finfo->buffer($file));
      } else {
        $content = new Content();
        return $content->RenderPage($response, $this, '404');
      }
    });

    $this->app->get('/site/files/{name:.*}', function (Request $request, Response $response, array $args) {
      $path = $args['name'];
      $conf = new Configuration();
      $config = $conf->GetConfig();
      $containingFolder = __DIR__ . '/../../../../site/files/';
      $filepath = $containingFolder . $path;
      $file = @file_get_contents($filepath);
      if ($file) {
        $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        $response->write($file);
        $explosion = explode('.', $filepath);
        $ext = array_pop($explosion);
        if ($ext === 'svg') return $response->withHeader('Content-Type', 'image/svg+xml');
        else return $response->withHeader('Content-Type', $finfo->buffer($file));
      } else {
        $content = new Content();
        return $content->RenderPage($response, $this, '404');
      }
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
