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
        'wrapper' => 'form_schema',
      ],
    ];
    $engine_value = (!empty($form_state->getValue('engine'))) ? $form_state->getValue('engine') : $elastic_appsearch_referenceui->getEngine();

    $form['field_title'] = [
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

    $form['fields_filter'] = [
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
    ];

    $form['fields_sort'] = [
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

    $form['field_url'] = [
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
  public function updateSchema($form, &$form_state) {

    $renderer = \Drupal::service('renderer');
    $title = $renderer->render($form['field_title']);
    $filter = $renderer->render($form['fields_filter']);
    $sort = $renderer->render($form['fields_sort']);
    $url = $renderer->render($form['field_url']);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#form_schema_title', $title));
    $response->addCommand(new ReplaceCommand('#form_schema_filter', $filter));
    $response->addCommand(new ReplaceCommand('#form_schema_sort', $sort));
    $response->addCommand(new ReplaceCommand('#form_schema_url', $url));

    return $response;
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
    \Drupal::cache()->set($cid, null);
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
      $engine_collection[$key] = $engine->label();
    }

    return $engine_collection;
  }

}
