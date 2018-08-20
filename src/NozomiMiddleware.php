<?php
/**
 * Created by PhpStorm.
 * User: afror
 * Date: 8/14/2018
 * Time: 9:13
 */

namespace Nozomi\Core;

use \Slim\Http\Request;
use \Slim\Http\Response;


class NozomiMiddleware
{
  public function __invoke(Request $request, Response $response, $next) {
    $response = $response->withHeader('X-Powered-By', 'Nozomi/0.0.1');
    $response = $next($request, $response);
    return $response;
  }
}