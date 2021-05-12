<?php

namespace Drupal\elastic_appsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_appsearch\Utility\Database;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'SearchUIBlock' block.
 *
 * @Block(
 *  id = "searchui_block",
 *  admin_label = @Translation("AppSearch UI Block"),
 * )
 */
class SearchUIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['searchui_settings'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Search UI Settings'),
      '#description' => $this->t('Provide the search ui settings to be used to render react package.'),
      '#options' => $this->getSearchUISettings(),
      '#default_value' => (isset($this->configuration['searchui_settings'])) ? $this->configuration['searchui_settings'] : '',
      '#size' => 5,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchUISettings() {
    $collection = [];
    $uis = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_referenceui')->loadMultiple();
    foreach ($uis as $ui) {
      $server = $ui->getEngineInstance()->getServerInstance();
      if ($server->status() && $server->isAvailable()) {
        $collection[$ui->id()] = sprintf(
          '%s -> %s -> %s',
          ucwords($server->label()),
          ucwords($ui->getEngine()),
          $ui->label()
        );
      }
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['searchui_settings'] = $form_state->getValue('searchui_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $engine_data = $this->buildEngineJson();
    $build = [];
    $build['#theme'] = 'searchui_block';
    $build['#content'][] = $this->configuration['searchui_settings'];
    $build['#engine'] = $engine_data['json'];
    $build['#attached']['library'] = [
      'elastic_appsearch/elastic_appsearch-library',
    ];

    $inlinejs = '';
    $inlinejs = "var appConfig =  JSON.parse('" . json_encode($engine_data['json']) . "');";

    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#attributes' => ['type' => 'text/javascript'],
        '#value' => $inlinejs,
      ],
      'elastic_appsearch_inlinejs'
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEngineJson() {

    $reference_ui_config_name = $this->configuration['searchui_settings'];

    $data = NULL;
    if (!empty($reference_ui_config_name)) {
      $this->referenceui = \Drupal::entityTypeManager()
        ->getStorage('elastic_appsearch_referenceui')
        ->load($reference_ui_config_name);

      $cid = $this->referenceui->id() . $this->referenceui->getEngine();
      $cache = \Drupal::cache()->get($cid);
      // Load from cache if available.
      if ($cache->data !== NULL) {
        $data = $cache->data;
      }
      else {
        $this->engine = $this->referenceui->getEngineInstance();
        $this->server = $this->engine->getServerInstance();
        $data = $this->renderEngineJson();
        // Set cache for future requests.
        \Drupal::cache()->set(
          $cid,
          $data,
          Cache::PERMANENT,
          $this->referenceui->getCacheTags()
        );
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEngineJson() {

    $engine_fields = $this->engine->getEngineFields();
    $field_facets = $field_sort = [];
    $sorts = $this->referenceui->getFieldsSort();
    $searchables = $this->referenceui->getFieldsFilterSearchable();

    foreach ($sorts as $sort) {
      $field_sort[] = [
        'title' => $engine_fields[$sort]['label'],
        'field' => $sort
      ];
    }

    $facets = $this->referenceui->getFieldsFilter();
    foreach ($facets as $facet) {
      $field_facets[] = [
        'title' => $engine_fields[$facet]['label'],
        'field' => $facet,
        'isFilterable' => (array_key_exists($facet, $searchables) && $searchables[$facet] != '0') ? TRUE : FALSE
      ];
    }

    $disjunctives = $this->referenceui->getFieldsFilterDisjunctive();

    return [
      'json' => [
        'engineName' => $this->engine->id(),
        'endpointBase' => $this->server->getHost(),
        'searchKey' => $this->server->getPublicKey(),
        'resultFields' => $this->engine->getEngineFields(TRUE),
        'sortFields' => $field_sort,
        'facets' => $field_facets,
        'disjunctive' => array_keys($disjunctives),
        'titleField' => $this->referenceui->getFieldTitle(),
        'urlField' => $this->referenceui->getFieldUrl(),
        '_ctags' => $this->referenceui->getCacheTags(),
        'searchables' => $searchables
      ]
    ];
  }

}
