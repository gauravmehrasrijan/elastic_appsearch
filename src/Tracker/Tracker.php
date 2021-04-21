<?php

namespace Drupal\elastic_appsearch\Tracker;
use Drupal\elastic_appsearch\Utility\Common;

/**
 * Class TrackerService.
 */
class Tracker {

  /**
   * Status value that represents items which are indexed in their latest form.
   */
  const STATUS_INDEXED = 0;

  /**
   * Status value that represents items which still need to be indexed.
   */
  const STATUS_NOT_INDEXED = 1;

  /**
   * The database connection used by this plugin.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|null
   */
  protected $timeService;

  protected $engine;

  public function getInstance($engine = NULL){

    if($engine){
      $this->engine = $engine;
    }

    return $this;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection used by this plugin.
   */
  public function getDatabaseConnection() {
    return $this->connection ?: \Drupal::database();
  }

  /**
   * Retrieves the time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  public function getTimeService() {
    return $this->timeService ?: \Drupal::time();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['indexing_order' => 'fifo'];
  }

  /**
   * Creates a SELECT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createSelectStatement() {
    $select = $this->getDatabaseConnection()->select('elastic_appsearch_item', 'eap');
    $select->condition('engine_id', $this->engine->id());
    return $select;
  }

  /**
   * Creates an INSERT statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Insert
   *   An INSERT statement.
   */
  protected function createInsertStatement() {
    return $this->getDatabaseConnection()->insert('elastic_appsearch_item')
      ->fields(['engine_id', 'datasource', 'item_id', 'changed', 'status']);
  }

  /**
   * Creates an UPDATE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   An UPDATE statement.
   */
  protected function createUpdateStatement() {
    return $this->getDatabaseConnection()->update('elastic_appsearch_item')
      ->condition('engine_id', $this->engine->id());
  }

  /**
   * Creates a DELETE statement for this tracker.
   *
   * @return \Drupal\Core\Database\Query\Delete
   *   A DELETE Statement.
   */
  protected function createDeleteStatement() {
    return $this->getDatabaseConnection()->delete('elastic_appsearch_item')
      ->condition('engine_id', $this->engine->id());
  }

  /**
   * Creates a SELECT statement which filters on the not indexed items.
   *
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   retrieved.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT statement.
   */
  protected function createRemainingItemsStatement($engine_id = NULL) {
    $select = $this->createSelectStatement();
    $select->fields('eap', ['item_id']);
    if ($engine_id) {
      $select->condition('engine_id', $engine_id);
    }
    $select->condition('eap.status', $this::STATUS_NOT_INDEXED, '=');
    // Use the same direction for both sorts to avoid performance problems.
    $order = 'DESC';
    $select->orderBy('eap.changed', $order);
    // Add a secondary sort on item ID to make the order completely predictable.
    $select->orderBy('eap.item_id', $order);

    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted(array $ids, $status=NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $engine_id = $this->engine->id();
      // Process the IDs in chunks so we don't create an overly large INSERT
      // statement.
      foreach (array_chunk($ids, 1000) as $ids_chunk) {
        // We have to make sure we don't try to insert duplicate items.
        $select = $this->createSelectStatement()
          ->fields('eap', ['item_id']);
        $select->condition('item_id', $ids_chunk, 'IN');
        $existing = $select
          ->execute()
          ->fetchCol();
        $existing = array_flip($existing);

        $insert = $this->createInsertStatement();
        foreach ($ids_chunk as $item_id) {
          if (isset($existing[$item_id])) {
            continue;
          }
          list($datasource_id) = Common::splitCombinedId($item_id);
          $insert->values([
            'engine_id' => $engine_id,
            'datasource' => $datasource_id,
            'item_id' => $item_id,
            'changed' => $this->getTimeService()->getRequestTime(),
            'status' => ($status) ? $status : $this::STATUS_NOT_INDEXED,
          ]);
        }
        if ($insert->count()) {
          $insert->execute();
        }
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated(array $ids = NULL) {
    
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : [NULL]);
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields([
          'changed' => $this->getTimeService()->getRequestTime(),
          'status' => $this::STATUS_NOT_INDEXED,
        ]);
        if ($ids_chunk) {
          $update->condition('item_id', $ids_chunk, 'IN');
        }
        // Update the status of unindexed items only if the item order is LIFO.
        // (Otherwise, an item that's regularly being updated might never get
        // indexed.)
        // $update->condition('status', self::STATUS_INDEXED);
        $update->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsUpdated($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $update = $this->createUpdateStatement();
      $update->fields([
        'changed' => $this->getTimeService()->getRequestTime(),
        'status' => $this::STATUS_NOT_INDEXED,
      ]);
      if ($datasource_id) {
        $update->condition('datasource', $datasource_id);
      }
      $update->execute();
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsIndexed(array $ids) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large UPDATE
      // statement.
      $ids_chunks = array_chunk($ids, 1000);
      foreach ($ids_chunks as $ids_chunk) {
        $update = $this->createUpdateStatement();
        $update->fields(['status' => $this::STATUS_INDEXED]);
        $update->condition('item_id', $ids_chunk, 'IN');
        $update->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted(array $ids = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      // Process the IDs in chunks so we don't create an overly large DELETE
      // statement.
      $ids_chunks = ($ids !== NULL ? array_chunk($ids, 1000) : [NULL]);
      foreach ($ids_chunks as $ids_chunk) {
        $delete = $this->createDeleteStatement();
        if ($ids_chunk) {
          $delete->condition('item_id', $ids_chunk, 'IN');
        }
        $delete->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackAllItemsDeleted($datasource_id = NULL) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $delete = $this->createDeleteStatement();
      if ($datasource_id) {
        $delete->condition('datasource', $datasource_id);
      }
      $delete->execute();
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItems($limit = -1) {
    try {
      $select = $this->createRemainingItemsStatement();
      if ($limit >= 0) {
        $select->range(0, $limit);
      }
      return $select->execute()->fetchCol();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createSelectStatement();
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexedItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createSelectStatement();
      $select->condition('eap.status', $this::STATUS_INDEXED);
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingItemsCount($datasource_id = NULL) {
    try {
      $select = $this->createRemainingItemsStatement();
      if ($datasource_id) {
        $select->condition('datasource', $datasource_id);
      }
      return (int) $select->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->logException($e);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityDelete($entity_id) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $this->getDatabaseConnection()
        ->delete('elastic_appsearch_item')
        ->condition('item_id', $entity_id)
        ->execute();
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityInsert($item_id, $engine_id) {
    $transaction = $this->getDatabaseConnection()->startTransaction();
    try {
      $insert = $this->createInsertStatement();
      list($datasource_id) = Common::splitCombinedId($item_id);
      $insert->values([
        'engine_id' => $engine_id,
        'datasource' => $datasource_id,
        'item_id' => $item_id,
        'changed' => $this->getTimeService()->getRequestTime(),
        'status' => $this::STATUS_INDEXED,
      ]);
    
      if ($insert->count()) {
        $insert->execute();
      }
    }
    catch (\Exception $e) {
      $this->logException($e);
      $transaction->rollBack();
      return 0;
    }
  }

}
