<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\elastic_appsearch\ElasticSearchInterface;
use Drupal\elastic_appsearch\Utility\Database;
use Drupal\elastic_appsearch\Utility\BatchHelper;

/**
 * Defines the Engine entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_appsearch_engine",
 *   label = @Translation("Engine"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\elastic_appsearch\EngineListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_appsearch\Form\EngineForm",
 *       "edit" = "Drupal\elastic_appsearch\Form\EngineForm",
 *       "delete" = "Drupal\elastic_appsearch\Form\EngineDeleteForm",
 *       "schema" = "Drupal\elastic_appsearch\Form\FieldSchemaForm",
 *       "reindex" = "Drupal\elastic_appsearch\Form\EngineReindexConfirmForm",
 *       "clear" = "Drupal\elastic_appsearch\Form\EngineClearIndexConfirmForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_appsearch\EngineHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "elastic_appsearch_engine",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/elastic-appsearch/engine/{elastic_appsearch_engine}",
 *     "add-form" = "/admin/config/search/elastic-appsearch/engine/add",
 *     "edit-form" = "/admin/config/search/elastic-appsearch/engine/{elastic_appsearch_engine}/edit",
 *     "delete-form" = "/admin/config/search/elastic-appsearch/engine/{elastic_appsearch_engine}/delete",
 *     "collection" = "/admin/config/search/elastic-appsearch/engine"
 *   }
 * )
 */
class Engine extends ConfigEntityBase implements EngineInterface {

  /**
   * The Engine ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Engine label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Engine language.
   *
   * @var string
   */
  protected $language;

  /**
   * The Engine Server refrence.
   *
   * @var string
   */
  protected $server;

  /**
   * The Engine datasources refrence.
   *
   * @var string
   */
  protected $datasources;

  /**
   * The Engine datasources field schema.
   *
   * @var string
   */
  protected $schema;

  /**
   * The Engine status.
   *
   * @var boolean
   */
  protected $status;

  /**
   * Elastic appsearch client.
   *
   * @var Drupal\elastic_appsearch\ElasticSearchInterface
   */
  public $client;

  protected $trackerInstance;

  public function getLanguage(){
    return $this->language;
  }

  public function getServer(){
    return $this->server;
  }

  public function datasources(){
    return $this->datasources;
  }

  public function getServerInstance(){
    return \Drupal::entityTypeManager()
      ->getStorage('elastic_appsearch_server')
      ->load($this->getServer());
  }

  public function getClient(){
    if($_server = $this->getServerInstance()){
      return $_server->getClient();
    }
  }

  public function getFields(){
    $collection = [];
    if(!empty($this->datasources())){
      foreach($this->datasources() as $bundle){
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $fields = $entityFieldManager->getFieldDefinitions('node', $bundle);
        foreach($fields as $key=>$field){
          if(!isset($collection[$key])){
            $collection[$key]['field'] = $field;
          }
          $collection[$key]['appears_in'][] = $bundle;
        }
      }
    }
    
    return $collection;
  }

  public function supportedtypes(){
    return [
      'text' => 'Text',
      'number' => 'Number',
      'date' => 'Date',
      'geolocation' => 'Geolocation'
    ];
  }

  public function setSchema($settings){
    $this->schema = $settings;
  }

  public function getEngineFields(){
    $collection = [];
    if(!empty($this->schema)){
      foreach($this->schema as $schema){
        $collection[$schema['field_id']] = $schema;
      }
    }
    return $collection;
  }

  public function getSchema(){
    return $this->schema;
  }

  public function getStatus(){
    return $this->status;
  }


  public function info(){
    $elasticlient = $this->getClient();

    if(!$elasticlient){
      return;
    }
    $engine = $elasticlient->getEngine($this->id());

    if($engine){
      return $engine;
    }
  }

  public function preSave(EntityStorageInterface $storage){
    if($this->isNew()){
      $this->getClient()->createEngine($this->id(), $this->getLanguage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if($this->getClient()){
      $this->getClient()->deleteEngine($this->id());
      parent::delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerInstance() {
    $this->trackerInstance = \Drupal::service('elastic_appsearch.tracker')->getInstance($this);
    return $this->trackerInstance;
  }

  public function getIndexItemsCount(){
    return Database::getNodeCount($this->datasources());
  }

  public function setItemsTrackable(){
    $nodes = Database::getNodes($this->datasources());
    $this->getTrackerInstance()->trackAllItemsDeleted();
    $this->getTrackerInstance()->trackItemsInserted($nodes);
  }

  public function indexDocuments($documents){
    return $this->getClient()->indexDocuments($this->id(), $documents);
  }

  public function deleteDocuments($documents){
    return $this->getClient()->deleteDocuments($this->id(), $documents);
  }

  public function performTasks($tasks){

    BatchHelper::setup($this, $tasks, ['limit'=>100, 'batch_size'=>100]);
    
  }

}
