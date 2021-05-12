<?php

namespace Drupal\elastic_appsearch;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Search ui entities.
 */
class ReferenceUIListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Search ui');
    $header['id'] = $this->t('Machine name');
    $header['server'] = $this->t('Server');
    $header['engine'] = $this->t('Engine');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['server'] = $entity->getEngineInstance()->getServerInstance()->label();
    $row['engine'] = $entity->getEngine();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $engine = \Drupal::request()->get('elastic_appsearch_engine');

    if (!empty($engine)) {
      $query = $this->getStorage()->getQuery()
        ->condition('engine', $engine, '=');
      return $query->execute();
    }

  }

}
