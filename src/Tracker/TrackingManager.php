<?php

namespace Drupal\elastic_appsearch\Tracker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\NodeInterface;
use Drupal\elastic_appsearch\Utility\Common;
use Drupal\elastic_appsearch\Entity\Engine;
use Drupal\elastic_appsearch\Utility\Database;

/**
 * Class TrackingManager.
 */
class TrackingManager {

  /**
   * Constructs a new TrackingManager object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function trackerInsertNode($node) {
    $this->doUpdate($node, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function trackerUpdateNode($node) {
    $this->doUpdate($node);
  }

  /**
   * {@inheritdoc}
   */
  public function trackerDeleteNode($node) {
    $engines = $this->getQualifyingEngines($node->getType());
    foreach ($engines as $engine) {
      $engine->deleteDocuments([$node->id()]);

      $node_id = $node->getType() . '/' . $node->id() . ':' . $node->get('langcode')->value;
      $engine->getTrackerInstance()->trackItemsDeleted([$node_id]);

    }
  }

  /**
   * {@inheritdoc}
   */
  private function doUpdate($node, $isNew = FALSE) {
    $engines = $this->getQualifyingEngines($node->getType());
    foreach ($engines as $engine) {
      $node_id = $node->getType() . '/' . $node->id() . ':' . $node->get('langcode')->value;
      $data = Database::prepareNodeToIndex($node_id, $engine->getEngineFields());
      $engine->indexDocuments($data);

      if ($isNew) {
        $engine->getTrackerInstance()->trackItemsInserted([$node_id], 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getQualifyingEngines($node_type) {

    $engines = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->loadMultiple();
    foreach ($engines as $engine) {
      $server = $engine->getServerInstance();

      if (
        !$server->status()
        || !$server->isAvailable()
        || !$engine->datasources()
        || (!in_array($node_type, $engine->datasources())
        || !$engine->status() || !$engine->info()
      )) {
        unset($engines[$engine->id()]);
      }
    }
    return $engines;
  }

}
