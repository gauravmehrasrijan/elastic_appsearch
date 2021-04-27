<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirm form for reindexing an index.
 */
class SyncEngineConfirmForm extends ConfirmFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexReindexConfirmForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $messenger = $container->get('messenger');

    return new static($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_sync_engine";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to sync %name, Make sure that the engine documents indexed on the server was originated from this drupal install only, You may otherwise sabotise the engine in case it is also in sync with other drupal instance?', ['%name' => 'engine name']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Indexed data will be cleared on the search server. Searches on this index will stop yielding results. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Return new Url('entity.elastic_appsearch_engine.canonical', ['elastic_appsearch_engine' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $elastic_appsearch_engine = NULL) {
    $this->engine = $elastic_appsearch_engine;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $engine = $this->getEntity();

    try {
      $engine->performTasks(['clear']);
    }
    catch (SearchApiException $e) {
      // Echo $e->getMessage(); exit;.
    }

    $form_state->setRedirect('entity.elastic_appsearch_engine.canonical', ['elastic_appsearch_engine' => $engine->id()]);
  }

}
