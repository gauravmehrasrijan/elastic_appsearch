<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Server entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_appsearch_server",
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
 *   config_prefix = "elastic_appsearch_server",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/elastic-appsearch/server/{elastic_appsearch_server}",
 *     "add-form" = "/admin/config/search/elastic-appsearch/server/add",
 *     "edit-form" = "/admin/config/search/elastic-appsearch/server/{elastic_appsearch_server}/edit",
 *     "delete-form" = "/admin/config/search/elastic-appsearch/server/{elastic_appsearch_server}/delete",
 *     "collection" = "/admin/config/search/elastic-appsearch/server"
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
   * The Server publicKey.
   *
   * @var string
   */
  protected $publicKey;


  /**
   * The Server status.
   *
   * @var bool
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    return $this->publicKey;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // The rest of the code only applies to updates.
    if (!isset($this->original)) {
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!empty($this->client[$this->id()])) {
      return $this->client[$this->id()];
    }

    $this->client[$this->id()] = \Drupal::service('elastic_appsearch.client')
      ->getInstance($this);
    return $this->client[$this->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getEngines(array $properties = []) {
    $storage = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine');
    return $storage->loadByProperties(['server' => $this->id()] + $properties);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    $is_available = TRUE;
    try {
      $instance = $this->getClient();
      if (!empty($instance)) {
        $engine = $instance->listEngines();
        return is_array($engine);
      }
    }
    catch (\Exception $e) {
      $is_available = FALSE;
      \Drupal::logger('elastic_appsearch')->notice('Unable to reach server - ' . $this->id());
    }

    return $is_available;

  }

}
