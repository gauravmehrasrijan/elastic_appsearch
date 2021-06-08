<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Class ReferenceUIForm.
 */
class ReferenceUIForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $elastic_appsearch_referenceui = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $elastic_appsearch_referenceui->label(),
      '#description' => $this->t("Label for the Search ui."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $elastic_appsearch_referenceui->id(),
      '#machine_name' => [
        'exists' => '\Drupal\elastic_appsearch\Entity\ReferenceUI::load',
      ],
      '#disabled' => !$elastic_appsearch_referenceui->isNew(),
    ];

    $form['engine'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Engine'),
      '#maxlength' => 255,
      '#options' => $this->getActiveEngines(),
      '#default_value' => $elastic_appsearch_referenceui->getEngine(),
      '#description' => $this->t("Please select from the available list of engines"),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateSchema'],
        'event' => 'change',
        'wrapper' => 'form_settings',
      ],
    ];

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('General Settings'),
    // Added.
      '#collapsible' => TRUE,
    // Added.
      '#collapsed' => TRUE,
      '#prefix' => '<div id="form_settings">',
      '#suffix' => '</div>',
    ];

    $engine_value = (!empty($form_state->getValue('engine'))) ? $form_state->getValue('engine') : $elastic_appsearch_referenceui->getEngine();

    $form['settings']['field_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Title'),
      '#maxlength' => 255,
      '#options' => $this->getEngineSchema($engine_value),
      '#default_value' => $elastic_appsearch_referenceui->getFieldTitle(),
      '#description' => $this->t("Used as the top-level visual identifier for every rendered result"),
      '#required' => TRUE,
      '#prefix' => '<div id="form_schema_title">',
      '#suffix' => '</div>',
    ];

    $form['settings']['fields_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Filter Fields'),
      '#multiple' => TRUE,
      '#options' => $this->getEngineSchema($engine_value),
      '#default_value' => $elastic_appsearch_referenceui->getFieldsFilter(),
      '#description' => $this->t("Faceted values rendered as filters and available as query refinement"),
      '#required' => FALSE,
      '#prefix' => '<div id="form_schema_filter">',
      '#suffix' => '</div>',
      '#attributes' => [
        'style' => 'width: 50em;'
      ],
      '#ajax' => [
        'callback' => [$this, 'updateFilters'],
        'event' => 'change',
        'wrapper' => 'form_searchable_fields',
      ],
    ];

    $fields_selected = (!empty($form_state->getValue('fields_filter'))) ?
      $form_state->getValue('fields_filter') : $elastic_appsearch_referenceui->getFieldsFilter();

    $available_fields = $this->getSearchableSelected($fields_selected, $engine_value);
    $defaults = $elastic_appsearch_referenceui->getFieldsFilterSearchable();
    $defaults_disjunctives = $elastic_appsearch_referenceui->getFieldsFilterDisjunctive();

    $form['settings']['extra']['fields_filter_disjunctive'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select fileters that are disjunctive in nature.'),
      '#options' => $available_fields,
      '#default_value' => ($defaults_disjunctives) ? $defaults_disjunctives : [],
      '#description' => $this->t("This will enable filtering for multiple values within selected facets independently"),
      '#required' => FALSE,
      '#prefix' => '<div id="form_searchable_fields">'
    ];

    $defaults_searchable = $elastic_appsearch_referenceui->getFieldsFilterSearchable();

    $form['settings']['extra']['fields_filter_searchable'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select fields to enable facet field search.'),
      '#options' => $available_fields,
      '#default_value' => ($defaults_searchable) ? $defaults_searchable : [],
      '#description' => $this->t("Faceted values rendered as filters and available as query refinement"),
      '#required' => FALSE,
      '#suffix' => '</div>'
    ];

    $form['settings']['fields_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Sort Fields'),
      '#multiple' => TRUE,
      '#options' => $this->getEngineSchema($engine_value),
      '#default_value' => $elastic_appsearch_referenceui->getFieldsSort(),
      '#description' => $this->t("Used to display result sorting options, ascending and descending"),
      '#required' => FALSE,
      '#prefix' => '<div id="form_schema_sort">',
      '#suffix' => '</div>',
      '#attributes' => [
        'style' => 'width: 50em;'
      ],
    ];

    $form['settings']['field_url'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Url Field'),
      '#options' => $this->getEngineSchema($engine_value),
      '#default_value' => $elastic_appsearch_referenceui->getFieldUrl(),
      '#description' => $this->t("Used as a result's link target, if applicable"),
      '#required' => TRUE,
      '#prefix' => '<div id="form_schema_url">',
      '#suffix' => '</div>'
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $elastic_appsearch_referenceui->status(),
      '#description' => $this->t('Status of the Search ui instance.'),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchableSelected($options, $engine_name) {
    $fields = $this->getEngineSchema($engine_name);
    foreach ($fields as $key => $value) {
      if (!in_array($key, $options)) {
        unset($fields[$key]);
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function updateSchema($form, &$form_state) {

    // Just return redendered settings.
    return $form['settings'];

  }

  /**
   * {@inheritdoc}
   */
  public function updateFilters($form, &$form_state) {

    // Just return redendered settings fields_filter_searchable.
    return $form['settings']['extra'];

  }

  /**
   * {@inheritdoc}
   */
  public function getEngineSchema($name) {

    $response = [];

    if ($name) {
      $engine = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->load($name);
      $schema_items = $engine->getClient()->getSchema($name);
      $engine_schema = $engine->getEngineFields();
      foreach ($schema_items as $field => $type) {
        if (isset($engine_schema[$field])) {
          $response[$field] = $engine_schema[$field]['label'] . " - (" . $type . ")";
        }
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    unset($form['fields_filter']['#needs_validation']);
    unset($form['fields_sort']['#needs_validation']);
    $form['fields_filter']['#validated'] = TRUE;
    $form['fields_sort']['#validated'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $elastic_appsearch_referenceui = $this->entity;

    $status = $elastic_appsearch_referenceui->save();
    $cid = $elastic_appsearch_referenceui->id() . $elastic_appsearch_referenceui->getEngine();
    \Drupal::cache()->set($cid, NULL);
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Search ui.', [
          '%label' => $elastic_appsearch_referenceui->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Search ui.', [
          '%label' => $elastic_appsearch_referenceui->label(),
        ]));
    }
    $form_state->setRedirectUrl($elastic_appsearch_referenceui->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveEngines() {
    $engines = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->loadMultiple();

    $engine_collection = [];

    foreach ($engines as $key => $engine) {
      $server = $engine->getServerInstance();
      if ($server->status() && $server->isAvailable()) {
        $engine_collection[$key] = $server->label() . '::' . $engine->label();
      }
    }

    return $engine_collection;
  }

}
