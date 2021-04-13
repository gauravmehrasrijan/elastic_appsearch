<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Server entities.
 */
interface ServerInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  public function getDescription();

  public function getHost();

  public function getStatus();

  public function getSecret();
  
}
