<?php

namespace Drupal\elastic_appsearch\Utility;

use Drupal\elastic_appsearch\Utility\Common;

class Database{

  public static function getNodeCount($datasources){
    $query = \Drupal::database()->select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', $datasources , 'IN');
    return $query->countQuery()->execute()->fetchField();
  }

  public static function getNodes($datasources){

    $collection = [];
    $query = \Drupal::database()->select('node', 'n')
      ->fields('n', ['type','nid','langcode'])
      ->condition('n.type', $datasources , 'IN')
      ->execute();

    foreach($query as $node){
      $collection[] = Common::createCombinedId($node);
    }

    return $collection;
    
  }

  public static function filterNodeId($node_id){
    return filter_var($node_id, FILTER_SANITIZE_NUMBER_INT);
  }

  public static function prepareNodeToIndex($nid, $_fields){
    $response = [];
    $node_id = static::filterNodeId($nid);
    $node = \Drupal\node\Entity\Node::load($node_id);
    $response['id'] = $node_id;
    foreach ($node->getFields() as $name => $field) {
      if(isset($_fields[$name])){
        static::mapEntityReference($response, $field, $name);
      }
    }
    return $response;
  }

  public static function mapEntityReference(&$response, $field, $name){
    $field_type = $field->getFieldDefinition()->getType();
    if(
      $field_type == 'entity_reference'
      && $field->getFieldDefinition()->getSetting('target_type') == 'taxonomy_term'
    ){
      foreach($field->referencedEntities() as $entity_reference){
        $response[$name][] = $entity_reference->getName();
      }
    }else{
      $response[$name] = $field->getString();
    }
  }

  public function getNodesByTaxonomyTermIds($termIds){
    $termIds = (array) $termIds;
    if(empty($termIds)){
      return NULL;
    }
  
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', array('nid'));
    $query->condition('ti.tid', $termIds, 'IN');
    $query->distinct(TRUE);
    $result = $query->execute();
  
    if($nodeIds = $result->fetchCol()){
      return Node::loadMultiple($nodeIds);
    }
  
    return NULL;
  }
  
}