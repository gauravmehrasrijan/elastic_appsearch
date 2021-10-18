<?php

namespace Drupal\elastic_appsearch\Utility;

use Drupal\elastic_appsearch\Entity\EngineInterface;
use Drupal\elastic_appsearch\Utility\Database;

/**
 * {@inheritdoc}
 */
class BatchHelper {

  const DEFAULTS = [
    'batch_size' => NULL,
    'limit' => -1
  ];

  /**
   * {@inheritdoc}
   */
  public static function setup(EngineInterface $engine, $jobs, $options) {

    $options = array_merge(static::DEFAULTS, $options);
    if ($engine->status() && $options['batch_size'] !== 0 && $options['limit'] !== 0) {
      // Define the search index batch definition.
      $batch_definition = [
        'operations' => static::getJobFuntions($jobs, [$engine, $options]),
        'finished' => [__CLASS__, 'finish'],
        'title' => 'Processing Engine to index nodes',
        'init_message' => 'Hold tight as we prepare to launch.',
        'error_message' => 'Oh! Something went wrong!!',
        'progress_message' => 'Completed about @percentage% of the indexing operation (@current of @total).',
      ];
      // Schedule the batch.
      batch_set($batch_definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getJobFuntions($jobs, $args) {
    $operations = [];
    foreach ($jobs as $job) {
      $operations[] = [[__CLASS__, $job], $args];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public static function index($engine, $options, &$context) {

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['batch_size'] = $options['batch_size'];
      $context['sandbox']['max'] = $engine->getIndexItemsCount();
    }

    // Index documents.
    $indexNodeCollection = [];
    $_fields = $engine->getEngineFields();

    $process_nodes = $engine->getTrackerInstance()->getRemainingItems($options['batch_size']);
    foreach ($process_nodes as $nid) {
      $indexNodeCollection[] = Database::prepareNodeToIndex($nid, $_fields);
      $context['sandbox']['progress']++;
      $context['sandbox']['current_node'] = $nid;
      $context['message'] = 'Processing node items to index ' . $nid;
    }
    if (!empty($indexNodeCollection)) {
      $result = $engine->indexDocuments($indexNodeCollection);

      \Drupal::logger('elastic_appsearch')->notice(json_encode($result));
    }

    $engine->getTrackerInstance()->trackItemsIndexed($process_nodes);

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }
    else {
      $context['finished'] = 1;
      $context['message'] = 'Items indexed successfully.';
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function wipe_all($engine, $options, &$context) {

    $batch_size = $options['batch_size'];

    // Get document count.
    $documents = $engine->listDocuments($engine->id(), 1, $batch_size);
    $max = $documents['meta']['page']['total_results'];
    $page_count = $documents['meta']['page']['total_pages'];

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['batch_size'] = $batch_size;
      $context['sandbox']['max'] = $page_count;
    }

    // Delete documents in batch.
    for ($i = 1; $i <= $page_count; $i++) {
      $delete_ids = [];
      $context['sandbox']['progress'] = $i;
      $context['sandbox']['current_node'] = $i;
      $context['message'] = 'Deleting Records in batch ' . $i . 'of ' . $batch_size;
      $documents = $engine->listDocuments($engine->id(), $i, $batch_size);

      foreach ($documents['results'] as $document) {
        $delete_ids[] = Database::filterNodeId($document['id']);
      }
      if (!empty($delete_ids)) {
        $engine->getClient()->deleteDocuments($engine->id(), $delete_ids);
      }
      unset($delete_ids);
    }

    $engine->getTrackerInstance()->trackAllItemsDeleted();

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }
    else {
      $context['finished'] = 1;
      $context['message'] = 'All documents deleted successfully';
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function clear($engine, $options, &$context) {
    // Mark all for re indexing.
    $engine->getTrackerInstance()->trackAllItemsUpdated();

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['batch_size'] = $options['batch_size'];
      $context['sandbox']['max'] = $engine->getIndexItemsCount();
    }

    // Clear documents.
    $deleteNodeCollection = [];
    $process_nodes = $engine->getTrackerInstance()->getRemainingItems($options['batch_size']);
    foreach ($process_nodes as $nid) {
      $deleteNodeCollection[] = Database::filterNodeId($nid);
      $context['sandbox']['progress']++;
      $context['sandbox']['current_node'] = $nid;
      $context['message'] = 'Deleting node items from index : ' . $nid;
    }
    if (!empty($deleteNodeCollection)) {
      $engine->getClient()->deleteDocuments($engine->id(), $deleteNodeCollection);
    }

    $engine->getTrackerInstance()->trackItemsDeleted($process_nodes);

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }
    else {
      $engine->setItemsTrackable();
      $context['finished'] = 1;
      $context['message'] = 'Items cleared from engine successfully.';
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function finished() {
    \Drupal::logger('elastic_appsearch')->notice("Finished in style");
  }

}
