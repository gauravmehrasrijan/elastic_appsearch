<?php

namespace Drupal\elastic_appsearch\Entity;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Server entity.
 *
 * @ConfigEntityType(
 *   id = "server",
 *   label = @Translation("Appsearch Server"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\elastic_appsearch\ServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_appsearch\Form\ServerForm",
 *       "edit" = "Drupal\elastic_appsearch\Form\ServerForm",
 *       "delete" = "Drupal\elastic_appsearch\Form\ServerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_appsearch\ServerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "server",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/elastic_appsearch/server/{server}",
 *     "add-form" = "/admin/config/search/elastic_appsearch/server/add",
 *     "edit-form" = "/admin/config/search/elastic_appsearch/server/{server}/edit",
 *     "delete-form" = "/admin/config/search/elastic_appsearch/server/{server}/delete",
 *     "collection" = "/admin/config/search/elastic_appsearch/server"
 *   }
 * )
 */
class Server extends ConfigEntityBase implements ServerInterface {

  /**
   * The Server ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Server label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Server description.
   *
   * @var string
   */
  protected $description;
  
  /**
   * The Server host.
   *
   * @var uri
   */
  protected $host;

  /**
   * The Server secret.
   *
   * @var string
   */
  protected $secret;


  /**
   * The Server status.
   *
   * @var boolean
   */
  protected $status;

  public $client;

  public function getDescription(){
    return $this->description;
  }

  public function getHost(){
    return $this->host;
  }

  public function getStatus(){
    return $this->status;
  }

  public function getSecret(){
    return $this->secret;
  }

  public function preSave(EntityStorageInterface $storage){
    parent::preSave($storage);

    // The rest of the code only applies to updates.
    if (!isset($this->original)) {
      return;
    }
  }

  public function getClient(){
    if(!empty($this->client)){
      return $this->client;
    }

    $this->client = \Drupal::service('elastic_appsearch.client')
      ->getInstance($this);
    return $this->client;
  }

  public function getEngines(array $properties = []){
    $storage = \Drupal::entityTypeManager()->getStorage('engine');
    return $storage->loadByProperties(['server' => $this->id()] + $properties);
  }

  public function isAvailable(){
    $client = \Drupal::service('elastic_appsearch.client')::connect(
      $this->host,
      $this->secret
    );
    $engine = $client->listEngines();

    if( is_array($engine) ){
      return TRUE;
    }else{
      return FALSE;
    }
  }
  
}
