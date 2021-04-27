<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\elastic_appsearch\Entity\EngineInterface;

/**
 * Class EngineStatusForm.
 */
class EngineStatusForm extends FormBase {

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new EngineStatusForm object.
   */
  public function __construct(
    MessengerInterface $messenger
  ) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'engine_status_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EngineInterface $engine = NULL) {

    $form['#engine'] = $engine;

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    // Add the "Index now" form.
    $form['engine'] = [
      '#type' => 'details',
      '#title' => $this->t('Start indexing now'),
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $has_remaining_items = ($engine->getTrackerInstance()->getRemainingItemsCount() > 0);
    $all_value = $this->t('all', [], ['context' => 'items to index']);
    $limit = [
      '#type' => 'textfield',
      '#default_value' => $all_value,
      '#size' => 4,
      '#attributes' => [
        'class' => ['search-api-limit'],
      ],
      '#disabled' => !$has_remaining_items,
    ];
    $batch_size = [
      '#type' => 'textfield',
    // $index->getOption('cron_limit', $this->config('search_api.settings')->get('default_cron_limit')),
      '#default_value' => 100,
      '#size' => 4,
      '#attributes' => [
        'class' => ['search-api-batch-size'],
      ],
      '#disabled' => !$has_remaining_items,
    ];

    $sentence = preg_split('/@(limit|batch_size)/', $this->t('Index @limit items in batches of @batch_size items'), -1, PREG_SPLIT_DELIM_CAPTURE);
    // Check if the sentence contains the expected amount of parts.
    if (count($sentence) === 5) {
      $first = $sentence[1];
      $form['engine'][$first] = ${$first};
      $form['engine'][$first]['#prefix'] = $sentence[0];
      $form['engine'][$first]['#suffix'] = $sentence[2];
      $second = $sentence[3];
      $form['engine'][$second] = ${$second};
      $form['engine'][$second]['#suffix'] = "{$sentence[4]} ";
    }
    else {
      // Sentence is broken. Use fallback method instead.
      $limit['#title'] = $this->t('Number of items to index');
      $form['engine']['limit'] = $limit;
      $batch_size['#title'] = $this->t('Number of items per batch run');
      $form['engine']['batch_size'] = $batch_size;
    }
    // Add the value "all" so it can be used by the validation.
    $form['engine']['all'] = [
      '#type' => 'value',
      '#value' => $all_value,
    ];
    $form['engine']['index_now'] = [
      '#type' => 'submit',
      '#value' => $this->t('Index now'),
      '#disabled' => !$has_remaining_items,
      '#name' => 'index_now',
    ];

    // Add actions for reindexing and for clearing the index.
    $form['actions']['#type'] = 'actions';
    $form['actions']['reindex'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all items and re-index'),
      '#name' => 'reindex',
      '#button_type' => 'danger',
    ];
    $form['actions']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all indexed data'),
      '#name' => 'clear',
      '#button_type' => 'danger',
    ];
    $form['actions']['rebuild_tracker'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-index tracked rows'),
      '#name' => 'rebuild_tracker',
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $engine = $form['#engine'];

    switch ($form_state->getTriggeringElement()['#name']) {
      case 'index_now':
        $engine->performTasks(['index']);
        break;

      case 'reindex':
        $form_state->setRedirect('entity.elastic_appsearch_engine.reindex', ['elastic_appsearch_engine' => $engine->id()]);
        break;

      case 'clear':
        $form_state->setRedirect('entity.elastic_appsearch_engine.clear', ['elastic_appsearch_engine' => $engine->id()]);
        break;

      case 'rebuild_tracker':
        $engine->getTrackerInstance()->trackAllItemsUpdated();
        $engine->performTasks(['index']);
        break;
    }

  }

}
