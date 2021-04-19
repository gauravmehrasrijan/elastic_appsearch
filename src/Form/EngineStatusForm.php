<?php

namespace Drupal\elastic_appsearch\Form;

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
      '#default_value' => 100, //$index->getOption('cron_limit', $this->config('search_api.settings')->get('default_cron_limit')),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
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

  public function setBatch($engine, $batch_size = 100, $limit = 10){
    if ($engine->status() && $batch_size !== 0 && $limit !== 0) {
      // Define the search index batch definition.
      $batch_definition = [
        'operations' => [
          [[__CLASS__, 'process'], [$engine, $batch_size, $limit]],
        ],
        'finished' => [__CLASS__, 'finish'],
        'title' => t('Processing Engine to index nodes'),
        'init_message' => t('Hold tight as we start.'),
        'error_message' => t('Oh! Something went wrong!!'),
        'progress_message' => static::t('Completed about @percentage% of the indexing operation (@current of @total).'),
      ];
      // Schedule the batch.
      batch_set($batch_definition);
    }
  }

  public static function process($engine, $batch_size, $limit, &$context){

    $engine->setItemsTrackable();

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['batch_size'] = $batch_size;
      $context['sandbox']['max'] = $engine->getIndexItemsCount();
    }

    
    $indexNodeCollection = [];

    $_fields = $engine->getEngineFields();
    $process_nodes = $engine->getTrackerInstance()->getRemainingItems($batch_size);
    foreach($process_nodes as $nid){
      
      $indexNodeCollection[] = static::prepareNodeToIndex($nid, $_fields);

      $context['sandbox']['progress']++;
      $context['sandbox']['current_node'] = $nid;
      $context['message'] = 'Processing node items to index ' . $nid;
    }

    $engine->indexDocuments($indexNodeCollection);

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
    }else{
      $context['finished'] = 1;
      $context['message'] = 'Items indexed successfully.';
    }

    $engine->getTrackerInstance()->trackItemsIndexed($process_nodes);

  }

  public static function prepareNodeToIndex($nid, $_fields){
    $response = [];
    $node_id = filter_var($nid, FILTER_SANITIZE_NUMBER_INT);
    $node = \Drupal\node\Entity\Node::load($node_id);
    $response['id'] = $node_id;
    foreach ($node->getFields() as $name => $field) {
      if(isset($_fields[$name])){
        // $response[$name] = $field->getString();
        $field_type = $field->getFieldDefinition()->getType();
        if($field_type == 'entity_reference'){
          $target_type = $field->getFieldDefinition()->getSetting('target_type');
          if ($target_type == 'taxonomy_term'){
            foreach($field->referencedEntities() as $entity_reference){
              $response[$name][] = $entity_reference->getName();
            }
          }
        }else{
          $response[$name]  = $field->getString();
        }
      }
    }
    return $response;
  }

  public static function finished(){
    \Drupal::logger('elastic_appsearch')->notice("Finished in style");
  }

}

//http://ccf.docksal/admin/config/search/elastic_appsearch/engine/ccflocal