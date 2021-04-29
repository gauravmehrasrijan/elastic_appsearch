<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class AppsearchForm.
 */
class AppsearchForm extends ConfigFormBase {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new AppsearchForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    Connection $database
  ) {
    parent::__construct($config_factory);
    $this->httpClient = $http_client;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'elastic_appsearch.appsearch',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appsearch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('elastic_appsearch.appsearch');
    $api_endpoint_helptext = 'You can find the API endpoint URL in the credentials sections of the App Search';
    $api_private_key_helptext = 'You can find the your API key URL in the credentials sections of the App Search.';

    $form['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Endpoint'),
      '#description' => $this->t($api_endpoint_helptext),
      '#maxlength' => 255,
      '#size' => 100,
      '#default_value' => $config->get('api_endpoint'),
    ];
    $form['api_private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Private Key'),
      '#description' => $this->t($api_private_key_helptext),
      '#default_value' => $config->get('api_private_key'),
    ];
    $form['engine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Engine Name'),
      '#description' => $this->t('Please enter the engine name as defined in appsearch dashboard'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('engine_name'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    try {
      \Drupal::service('elastic_appsearch.client')::connect(
        $form_state->getValue('api_endpoint'),
        $form_state->getValue('api_private_key'),
        $form_state->getValue('engine_name')
      );
    }
    catch (\Exception $e) {
      $form_state->setErrorByName(
        'api_private_key',
        "Unable to make connection to the endpoint using the providec key: " . $e->getMessage()
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('elastic_appsearch.appsearch')
      ->set('api_endpoint', $form_state->getValue('api_endpoint'))
      ->set('api_private_key', $form_state->getValue('api_private_key'))
      ->set('engine_name', $form_state->getValue('engine_name'))
      ->save();

      $this->messenger()->addMessage($this->t('Connection to the endpoint is successfull using the provided key.'));
      
  }

}
