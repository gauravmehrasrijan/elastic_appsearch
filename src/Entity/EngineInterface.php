<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Engine entities.
 */
interface EngineInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  public function getLanguage();

  public function getFields();

  public function getEngineFields();
  
}
