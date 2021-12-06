<?php

namespace Drupal\elastic_appsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
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
      '#options' => $this->getSearchUiSettings(),
      '#default_value' => (isset($this->configuration['searchui_settings'])) ? $this->configuration['searchui_settings'] : '',
      '#size' => 5,
      '#weight' => '0',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchUiSettings() {
    $collection = [];
    $uis = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_referenceui')->loadMultiple();
    foreach ($uis as $ui) {
      $engine = $ui->getEngineInstance();
      if ($engine) {
        $server = $engine->getServerInstance();
        if ($server->status() && $server->isAvailable()) {
          $collection[$ui->id()] = sprintf(
            '%s -> %s -> %s',
            ucwords($server->label()),
            ucwords($ui->getEngine()),
            $ui->label()
          );
        }
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
    $seachui_settings = '';
    $config = $this->getConfiguration();
    $block = (isset($config['block_attr'])) ? $config['block_attr'] : NULL;
    if ($block) {
      $seachui_settings = $config['block_attr']->get('field_select_search_ui_settings')->getValue()[0]['value'];
    }
    else {
      $seachui_settings = $this->configuration['searchui_settings'];
    }
    $engine_data = $this->buildEngineJson($seachui_settings);
    $build = [];
    $build['#theme'] = 'searchui_block';
    $build['#content'][] = $seachui_settings;
    $build['#engine'] = $engine_data['json'];
    $build['#attached']['library'] = [
      'elastic_appsearch/elastic_appsearch-library',
    ];
    $inlinejs = '';
    $inlinejs = "var appConfig =  JSON.parse('" . json_encode($engine_data['json']) . "');";
    $inlinejs .= 'setTimeout(function(){jQuery(".rc-pagination-item a").click(function(){jQuery("html, body").animate({scrollTop:0},1e3,"swing")})},5e3);';
    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#attributes' => ['type' => 'text/javascript'],
        '#value' => $inlinejs,
      ],
      'elastic_appsearch_inlinejs',
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEngineJson($reference_ui_config_name) {
    $data = NULL;
    if (!empty($reference_ui_config_name)) {
      $this->referenceui = \Drupal::entityTypeManager()
        ->getStorage('elastic_appsearch_referenceui')
        ->load($reference_ui_config_name);
      $cid = $this->referenceui->id() . $this->referenceui->getEngine();
      $cache = \Drupal::cache()->get($cid);
      // Load from cache if available.
      if (isset($cache->data) && $cache->data !== NULL) {
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
    $engine_fields_key = $this->engine->getEngineFields(TRUE);
    $field_facets = $field_sort = [];
    $sorts = $this->referenceui->getFieldsSort();
    $searchables = $this->referenceui->getFieldsFilterSearchable();
    foreach ($sorts as $sort) {
      $field_sort[] = [
        'title' => $engine_fields[$sort]['label'],
        'field' => $sort,
      ];
    }
    $facets = $this->referenceui->getFieldsFilter();
    foreach ($facets as $facet) {
      $field_facets[] = [
        'title' => $engine_fields[$facet]['label'],
        'field' => $facet,
        'isFilterable' => (array_key_exists($facet, $searchables) && $searchables[$facet] != '0') ? TRUE : FALSE,
      ];
    }
    $disjunctives = $this->referenceui->getFieldsFilterDisjunctive();
    return [
      'json' => [
        'engineName' => $this->engine->id(),
        'endpointBase' => $this->server->getHost(),
        'searchKey' => $this->server->getPublicKey(),
        'resultFields' => array_merge($engine_fields_key, ['body_summary']),
        'sortFields' => $field_sort,
        'facets' => $field_facets,
        'disjunctive' => array_keys($disjunctives),
        'titleField' => $this->referenceui->getFieldTitle(),
        'urlField' => $this->referenceui->getFieldUrl(),
        '_ctags' => $this->referenceui->getCacheTags(),
        'searchables' => $searchables,
      ],
    ];
  }

}
