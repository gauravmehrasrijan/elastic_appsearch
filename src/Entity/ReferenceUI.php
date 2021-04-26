<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Reference ui entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_appsearch_referenceui",
 *   label = @Translation("Reference ui"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\elastic_appsearch\ReferenceUIListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elastic_appsearch\Form\ReferenceUIForm",
 *       "edit" = "Drupal\elastic_appsearch\Form\ReferenceUIForm",
 *       "delete" = "Drupal\elastic_appsearch\Form\ReferenceUIDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\elastic_appsearch\ReferenceUIHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "elastic_appsearch_referenceui",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/elastic-appsearch/referenceui/{elastic_appsearch_referenceui}",
 *     "add-form" = "/admin/config/search/elastic-appsearch/referenceui/add",
 *     "edit-form" = "/admin/config/search/elastic-appsearch/referenceui/{elastic_appsearch_referenceui}/edit",
 *     "delete-form" = "/admin/config/search/elastic-appsearch/referenceui/{elastic_appsearch_referenceui}/delete",
 *     "collection" = "/admin/config/search/elastic-appsearch/referenceui"
 *   }
 * )
 */
class ReferenceUI extends ConfigEntityBase implements ReferenceUIInterface {

  /**
   * The Reference ui ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Reference ui label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Reference ui engine.
   *
   * @var string
   */
  protected $engine;

  /**
   * The Reference ui field title.
   *
   * @var string
   */
  protected $field_title;

  /**
   * The Reference ui field url.
   *
   * @var string
   */
  protected $field_url;

  /**
   * The Reference ui field filters.
   *
   * @var sequence
   */
  protected $fields_filter;

  /**
   * The Reference ui fields sort.
   *
   * @var sequence
   */
  protected $fields_sort;

  public function getEngine(){
    return $this->engine;
  }

  public function getFieldTitle(){
    return $this->field_title;
  }

  public function getFieldUrl(){
    return $this->field_url;
  }

  public function getFieldsFilter(){
    return $this->fields_filter;
  }

  public function getFieldsSort(){
    return $this->fields_sort;
  }


}
