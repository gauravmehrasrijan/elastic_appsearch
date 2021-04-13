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
}