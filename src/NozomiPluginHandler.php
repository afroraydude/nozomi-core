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
  private $plugins = [];

  private $sidebars = [];

  public function registerPlugin(NozomiPlugin $plugin) {
    $sidebar = $plugin->sidebarHTML;

    if (is_string($sidebar)) {
      array_push($this->sidebars, $sidebar);
    }

    array_push($this->plugins, $plugin);
  }

  public function getPlugins() {
    return $this->plugins;
  }

  public function getSidebars() {
    return $this->sidebars;
  }
}
