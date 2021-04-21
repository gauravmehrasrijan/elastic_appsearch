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
   *
   * Note that this function implements tracking only on behalf of the "Content
   * Entity" datasource defined in this module, not for entity-based datasources
   * in general. Datasources defined by other modules still have to implement
   * their own mechanism for tracking new/updated/deleted entities.
   *
   * Independent of datasources, however, this will also call
   * \Drupal\search_api\Utility\TrackingHelper::trackReferencedEntityUpdate() to
   * attempt to mark all items for reindexing that indirectly indexed changed
   * fields of this entity.
   *
   * @see \Drupal\search_api\Plugin\search_api\datasource\ContentEntityTrackingManager::entityUpdate()
   * @see \Drupal\search_api\Utility\TrackingHelper::trackReferencedEntityUpdate()
   */

  public function trackerInsertNode($node){
    $this->doUpdate($node, TRUE);
  }

  public function trackerUpdateNode($node){
    $this->doUpdate($node);
  }

  public function trackerDeleteNode($node){
    $engines = $this->getQualifyingEngines($node->getType());
    foreach($engines as $engine){
      $engine->deleteDocuments([$node->id()]);
      
      $node_id = $node->getType() . '/' . $node->id() . ':' . $node->get('langcode')->value;
      $engine->getTrackerInstance()->trackItemsDeleted([$node_id]);
      
    }
  }

  private function doUpdate($node, $isNew = FALSE){
    $engines = $this->getQualifyingEngines($node->getType());
    foreach($engines as $engine){
      $node_id = $node->getType() . '/' . $node->id() . ':' . $node->get('langcode')->value;
      $data = Database::prepareNodeToIndex($node_id, $engine->getEngineFields());
      $engine->indexDocuments($data);
      
      if($isNew){
        $engine->getTrackerInstance()->trackItemsInserted([$node_id],0);
      }
    }
  }


  private function getQualifyingEngines($node_type){
    
    $engines = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->loadMultiple();
    foreach($engines  as $engine){
      if(
        !$engine->datasources()
        || (!in_array( $node_type, $engine->datasources())
        || !$engine->status() || !$engine->info()
      )){
        unset($engines[$engine->id()]);
      }
    }
    return $engines;
  }

}
