<?php

namespace Drupal\elastic_appsearch\Utility;

use Drupal\node\Entity\Node;
use Drupal\elastic_appsearch\Utility\Common;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

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
      ->condition('n.status', 1, '=');
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
      ->condition('n.status', 1, '=')
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
      if (isset($_fields[$name])) {
        $field_type = $node->get($name)->getFieldDefinition()->getType();
        static::mapFieldValues($node, $field_type, $name, $field, $response);
      }
    }
    return $response;
  }

  /**
   * Map Field Values.
   */
  public static function mapFieldValues($node, $field_type, $name, $field, &$response) {
    switch ($field_type) {
      case 'text_with_summary':
        $summary = $node->get($name)->summary;
        $response[$name . '_summary'] = $summary;
        try {
          $render_array = $node->$name->view('full');
          $rendered = \Drupal::service('renderer')->renderRoot($render_array);
          if (is_object($rendered)) {
            $response[$name] = trim(html_entity_decode(strip_tags($rendered->__toString())));
          }
          else {
            $response[$name] = trim(html_entity_decode(strip_tags($field->getString())));
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('elastic_appsearch')->notice('Failed to render HTML Body for node : ' . $node->id());
          \Drupal::logger('elastic_appsearch')->error($e->getMessage());
          $response[$name] = trim(html_entity_decode(strip_tags($field->getString())));
        }

        break;

      case 'path':
        $path = explode(', ', $field->getString());
        $response[$name] = ($path[0]) ? $path[0] : '';
        break;

      case 'text':
      case 'text_long':
        $response[$name] = html_entity_decode($field->getString());
        break;

      case 'entity_reference':
        self::setEntityReference($node, $field_type, $name, $field, $response);
        break;

      default:
        $response[$name] = html_entity_decode($field->getString());
    }
  }

  /**
   * Set Entity Reference.
   */
  public static function setEntityReference($node, $field_type, $name, $field, &$response) {
    if ($field->getFieldDefinition()->getSetting('target_type') == 'taxonomy_term') {
      foreach ($field->referencedEntities() as $entity_reference) {
        $response[$name][] = $entity_reference->getName();
      }
    }
    elseif ($field->getFieldDefinition()->getSetting('target_type') == 'media') {
      foreach ($field->referencedEntities() as $entity_reference) {
        try {
          $target_id = $field->getString();
          $media = Media::load($target_id);
          $media_url = ImageStyle::load('medium')->buildUrl($media->image->entity->getFileUri());
          $response[$name] = $media_url;
        }
        catch (\Exception $e) {
          // Do nothing for now. will decide later.
        }
      }
    }
    else {
      $response[$name] = $field->getString();
    }
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
