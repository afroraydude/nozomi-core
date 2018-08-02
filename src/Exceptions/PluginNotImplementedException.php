<?php
/**
 * Created by PhpStorm.
 * User: afror
 * Date: 8/2/2018
 * Time: 14:58
 */

namespace Nozomi\Core\Exceptions;


class PluginNotImplementedException extends Exception
{
  public function __construct($message, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

  public function customFunction() {
    echo "A custom function for this type of exception\n";
  }
}