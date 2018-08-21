<?php
/**
 * Created by PhpStorm.
 * User: afror
 * Date: 8/1/2018
 * Time: 9:33
 */

namespace Nozomi\Core;


class AuthorizationMiddleware
{
  private $level;

  function __construct(int $permLevel) {
    $this->level = $permLevel;
  }

    public function __invoke($request, $response, $next) {
    $conf = new Configuration();
    $config = $conf->GetConfig();
    if ($config) {
      $token = $_SESSION['token'];
      $auth = new Authorization();
      if (!$auth->auth($token, $this->level)) {
        $response = $response->withRedirect('/nozomi/login');
      } else {
        $response = $next($request, $response);
      }

    } else {
      $response = $response->withRedirect('/');
    }
    return $response;
  }
}