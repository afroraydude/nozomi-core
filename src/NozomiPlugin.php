<?php
/**
 * Created by PhpStorm.
 * User: afror
 * Date: 8/2/2018
 * Time: 15:19
 */

namespace Nozomi\Core;


class NozomiPlugin
{
  private $app;

  public function __construct( \Slim\App $app )
  {
    $this->app = $app;
  }

  public function registerRoutes(\Slim\App $app) {

  }
}