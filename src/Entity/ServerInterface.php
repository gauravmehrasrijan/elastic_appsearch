<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Server entities.
 */
interface ServerInterface extends ConfigEntityInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */
  public function getDescription();

  /**
   * {@inheritdoc}
   */
  public function getHost();

  /**
   * {@inheritdoc}
   */
  public function getStatus();

  /**
   * {@inheritdoc}
   */
  public function getSecret();

}
