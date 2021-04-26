<?php

namespace Drupal\elastic_appsearch;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;


/**
 * Provides a listing of Engine entities.
 */
class EngineListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Engine');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity instanceof EngineInterface) {
      $route_parameters['elastic_appsearch_enginie'] = $entity->id();
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 20,
        'url' => new Url('elastic_appsearch.elastic_appsearch_engine.canonical', $route_parameters),
      ];
    }

    return $operations;
  }
}
