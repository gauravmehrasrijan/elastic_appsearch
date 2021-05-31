<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EngineForm.
 */
class EngineForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $engine = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $engine->label(),
      '#pattern' => '^[aA-zZ\d]+$',
      '#pattern_error' => 'Special charcters not allowed',
      '#description' => $this->t("Lowercase letters, numbers with no special charcters and spaces"),
      '#required' => TRUE,
    ];
    $form['label']['#attributes']['autocomplete'] = 'off';

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Engine Language'),
      '#maxlength' => 255,
      '#options' => $this->getSupportedLanguages(),
      '#default_value' => $engine->getLanguage(),
      '#description' => $this->t("Please select from the available list of supported languages"),
      '#required' => TRUE,
    ];

    $form['server'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Server'),
      '#maxlength' => 255,
      '#options' => $this->getActiveEngines(),
      '#default_value' => $engine->getServer(),
      '#description' => $this->t("Please select from the available list of supported languages"),
      '#required' => TRUE,
    ];

    $form['datasources'] = [
      '#type' => 'select',
      '#title' => $this->t('Select content type'),
      '#multiple' => TRUE,
      '#size' => 20,
      '#options' => $this->getAvailbleContentTypes(),
      '#default_value' => $engine->datasources(),
      '#description' => $this->t("Select from the available content types to be index on engine"),
      '#required' => TRUE,
      '#attributes' => [
        'style' => 'width: 50em;'
      ],
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $engine->getStatus(),
      '#description' => $this->t('Enable disable the engine from here.'),
      '#required' => FALSE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $engine->id(),
      '#machine_name' => [
        'exists' => '\Drupal\elastic_appsearch\Entity\Engine::load',
      ],
      '#disabled' => !$engine->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * Fetch available content types available in system.
   */
  public function getAvailbleContentTypes() {
    $node_types = NodeType::loadMultiple();
    // If you need to display them in a drop down:
    $types = [];
    foreach ($node_types as $node_type) {
      $types[$node_type->id()] = $node_type->label();
    }
    return $types;
  }

  /**
   * Returns array of languages supported by appsearch.
   */
  public function getSupportedLanguages() {
    return [
      'Universal' => 'Universal',
      'zh' => 'Chinese',
      'da' => 'Danish',
      'de' => 'Genman',
      'it' => 'Italy',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'pt' => 'Portuguese',
      'pt-br' => 'Portuguese (Brazil)',
      'ru' => 'Russian',
      'es' => 'Spanish',
      'th' => 'Thai'
    ];
  }

  /**
   * Get available engines.
   */
  public function getActiveEngines() {
    $servers = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_server')->loadMultiple();

    $server_collection = [];

    foreach ($servers as $key => $server) {
      if ($server->status() && $server->isAvailable()) {
        $server_collection[$key] = $server->label();
      }
    }

    return $server_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $engine = $this->entity;
    $status = $engine->save();

    if ($status) {
      $engine->setItemsTrackable();
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Engine.', [
          '%label' => $engine->label(),
        ]));
        $redirect = $engine->toUrl('schema');
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Engine.', [
          '%label' => $engine->label(),
        ]));
        $redirect = $engine->toUrl('canonical');
    }
    $form_state->setRedirectUrl($redirect);
  }

}
