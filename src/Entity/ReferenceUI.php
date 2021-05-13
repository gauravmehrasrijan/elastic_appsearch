<?php

namespace Drupal\elastic_appsearch\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Search ui entity.
 *
 * @ConfigEntityType(
 *   id = "elastic_appsearch_referenceui",
 *   label = @Translation("Search ui"),
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
   * The Search ui ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Search ui label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Search ui engine.
   *
   * @var string
   */
  protected $engine;

  /**
   * The Search ui field title.
   *
   * @var string
   */
  protected $field_title;

  /**
   * The Search ui field url.
   *
   * @var string
   */
  protected $field_url;

  /**
   * The Search ui field filters.
   *
   * @var sequence
   */
  protected $fields_filter;

  /**
   * The Search searchable field filters.
   *
   * @var sequence
   */
  protected $fields_filter_searchable;

  /**
   * The Search ui field filters.
   *
   * @var sequence
   */
  protected $fields_filter_disjunctive;

  /**
   * The Search ui fields sort.
   *
   * @var sequence
   */
  protected $fields_sort;

  /**
   * {@inheritdoc}
   */
  public function getEngine() {
    return $this->engine;
  }

  /**
   *
   */
  public function getEngineInstance() {
    return \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->load($this->getEngine());
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTitle() {
    return $this->field_title;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldUrl() {
    return $this->field_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsFilter() {
    return $this->fields_filter;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsSort() {
    return $this->fields_sort;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsFilterSearchable() {
    return $this->fields_filter_searchable;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsFilterDisjunctive() {
    return $this->fields_filter_disjunctive;
  }

}
