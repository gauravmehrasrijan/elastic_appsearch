<?php

namespace Drupal\elastic_appsearch\Utility;

use Drupal\node\Entity\Node;
use Drupal\elastic_appsearch\Utility\Common;

/**
 * {@inheritdoc}
 */
class Database {

  /**
   * {@inheritdoc}
   */
  public static function getNodeCount($datasources) {
    $query = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', $datasources, 'IN')
      ->condition('n.status',1, '=');
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public static function getNodes($datasources) {

    $collection = [];
    $query = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', ['type', 'nid', 'langcode'])
      ->condition('n.type', $datasources, 'IN')
      ->condition('n.status',1, '=')
      ->execute();

    foreach ($query as $node) {
      $collection[] = Common::createCombinedId($node);
    }

    return $collection;

  }

  /**
   * {@inheritdoc}
   */
  public static function filterNodeId($node_id) {
    return filter_var($node_id, FILTER_SANITIZE_NUMBER_INT);
  }

  /**
   * {@inheritdoc}
   */
  public static function prepareNodeToIndex($nid, $_fields) {
    $response = [];
    $node_id = static::filterNodeId($nid);
    $node = Node::load($node_id);
    $response['id'] = $node_id;
    foreach ($node->getFields() as $name => $field) {
      // Get Type.
      $field_type = $node->get($name)->getFieldDefinition()->getType();
      
      switch($field_type) {
        case 'text_with_summary':
          $field_name = 'body';
          $render_array = $node->$field_name->view('full');
          $rendered = \Drupal::service('renderer')->renderRoot($render_array);
          $response[$name] = $rendered->__toString();
          break;
        case 'path':
          $path = explode(', ', $field->getString());
          $response[$name] = ($path[0]) ? $path[0] : '';
          break;
        case 'text':
        case 'text_long': 
          $response[$name]  = $field->getString();
          break;
        case 'entity_reference':
          if($field->getFieldDefinition()->getSetting('target_type') == 'taxonomy_term'){
            foreach($field->referencedEntities() as $entity_reference){
              $response[$name][] = $entity_reference->getName();
            }
          }else{
            $response[$name] = $field->getString();
          }
          break;
        default:
          $response[$name]  = $field->getString();
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodesByTaxonomyTermIds($termIds) {
    $termIds = (array) $termIds;
    if (empty($termIds)) {
      return NULL;
    }

    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', ['nid']);
    $query->condition('ti.tid', $termIds, 'IN');
    $query->distinct(TRUE);
    $result = $query->execute();

    if ($nodeIds = $result->fetchCol()) {
      return Node::loadMultiple($nodeIds);
    }

    return NULL;
  }

}
