<?php
/**
 * Created by PhpStorm.
 * User: afror
 * Date: 8/2/2018
 * Time: 15:23
 */

namespace Nozomi\Core;


class NozomiPluginHandler
{
  private $plugins = Array();

  public function registerPlugin( NozomiPlugin $plugin) {
    array_push($this->plugins, $plugin);
  }

  public function getPlugins() {
    return $this->plugins;
  }
}
