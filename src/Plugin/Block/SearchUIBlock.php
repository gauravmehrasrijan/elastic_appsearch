<?php

namespace Drupal\elastic_appsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_appsearch\Utility\Database;

/**
 * Provides a 'SearchUIBlock' block.
 *
 * @Block(
 *  id = "searchui_block",
 *  admin_label = @Translation("Search UI Block"),
 * )
 */
class SearchUIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
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
      '#default_value' => $this->configuration['searchui_settings'],
      '#size' => 5,
      '#weight' => '0',
    ];

    return $form;
  }

  public function getSearchUISettings(){
    $collection = [];
    $uis = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_referenceui')->loadMultiple();
    foreach($uis as $ui){
      $collection[$ui->id()] = sprintf(
        '%s -> %s -> %s',
        ucwords($ui->getEngineInstance()->getServer()),
        ucwords($ui->getEngine()),
        $ui->label()
      );
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
    $build['#fields'] = $engine_data['fields'];
    $build['#attached']['library'] = [
      'elastic_appsearch/elastic_appsearch-library',
    ];

    $inlinejs = '';
    $inlinejs = "var appConfig =  JSON.parse('" . json_encode($engine_data['json']) . "');";
    $inlinejs .= "var eap_fields = JSON.parse('" . json_encode($engine_data['fields']) . "')";

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

  public function buildEngineJson(){
    
    $reference_ui_config_name = $this->configuration['searchui_settings'];

    $data = NULL;
    
    if(!empty($reference_ui_config_name)){
      $this->referenceui = \Drupal::entityTypeManager()
      ->getStorage('elastic_appsearch_referenceui')
      ->load($reference_ui_config_name);

      $cid = $this->referenceui->id() . $this->referenceui->getEngine();

      if ($cache = \Drupal::cache()->get($cid)) {
        $data = $cache->data;
      }
      else if($this->referenceui){
        $this->engine = $this->referenceui->getEngineInstance();
        $this->server = $this->engine->getServerInstance();
        $data = $this->renderEngineJson();
        \Drupal::cache()->set($reference_ui_config_name, $data);
      }
    }
    return $data;
  }

  public function renderEngineJson(){
    $field_facets = $field_sort = [];
    $sorts = $this->referenceui->getFieldsSort();
    foreach($sorts as $sort){
      $field_sort[] = $sort;
    }

    $facets = $this->referenceui->getFieldsFilter();
    foreach($facets as $facet){
      $field_facets[] = $facet;
    }

    return [
      'json' => [
        'engineName' => $this->engine->id(),
        'endpointBase' => $this->server->getHost(),
        'searchKey' => $this->server->getPublicKey(),
        'resultFields' => $this->engine->getEngineFields(TRUE),
        'sortFields' => $field_sort,
        'facets' => $field_facets,
        'titleField' => $this->referenceui->getFieldTitle(),
        'urlField' => $this->referenceui->getFieldUrl()
      ],
      'fields' => $this->engine->getEngineFields()
    ];
  }

}
