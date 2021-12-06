<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Server Form.
 */
class ServerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $api_endpoint_helptext = 'You can find the API endpoint URL in the credentials sections of the App Search';

    $server = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $server->label(),
      '#description' => $this->t("Label for the Server."),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Short description of the server.'),
      '#maxlength' => 255,
      '#default_value' => $server->getDescription(),
      '#description' => $this->t("Label for the Server."),
      '#required' => FALSE,
    ];

    $form['host'] = [
      '#type' => 'url',
      '#title' => $this->t('Backend Endpoint'),
      '#maxlength' => 255,
      '#default_value' => $server->getHost(),
      '#description' => $api_endpoint_helptext,
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backend Secret Key'),
      '#maxlength' => 255,
      '#default_value' => $server->getSecret(),
      '#description' => $api_endpoint_helptext,
      '#required' => TRUE,
    ];

    $form['publicKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backend Public Key'),
      '#maxlength' => 255,
      '#default_value' => $server->getPublicKey(),
      '#description' => $this->t('Public key used by react search ui.'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $server->getStatus(),
      '#description' => $this->t('Enable disable the server from here.'),
      '#required' => FALSE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $server->id(),
      '#machine_name' => [
        'exists' => '\Drupal\elastic_appsearch\Entity\Server::load',
      ],
      '#disabled' => !$server->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $server = $this->entity;
    $status = $server->save();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Server.', [
          '%label' => $server->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Server.', [
          '%label' => $server->label(),
        ]));
    }
    $form_state->setRedirectUrl($server->toUrl('collection'));
  }

}
