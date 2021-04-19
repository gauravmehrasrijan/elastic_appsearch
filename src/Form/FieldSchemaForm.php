<?php

namespace Drupal\elastic_appsearch\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the fields of a search index.
 */
class FieldSchemaForm extends EntityForm {

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The shared temporary storage for unsaved search indexes.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The data type plugin manager.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexFieldsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    MessengerInterface $messenger) {
    
    $this->entityTypeManager = $entity_type_manager;
    
    $this->renderer = $renderer;
    
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    $renderer = $container->get('renderer');
    $messenger = $container->get('messenger');

    return new static(
      $entity_type_manager,
      $renderer,
      $messenger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_appsearch_schema_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['description']['#markup'] = $this->t('<p>The data type of a field determines how it can be used for searching and filtering. The boost is used to give additional weight to certain fields, for example titles or tags.</p> <p>For information about the data types available for indexing, see the <a href=":url">data types table</a> at the bottom of the page.</p>', [':url' => '#search-api-data-types-table']);
    $engine = $this->entity;
    $fields = $engine->getFields();
    $form_state->disableCache();

    // Set an appropriate page title.
    $form['#title'] = $this->t('Manage fields for search engine %label', ['%label' => $engine->label()]);
    $form['#tree'] = TRUE;

    if(!empty($fields)){
      $form['_general'] = $this->buildFieldsTable($fields);
      $form['_general']['#title'] = $this->t('General');
    }

    $form['actions'] = $this->actionsElement($form, $form_state);

    return $form;
  }

  /**
   * Builds the form fields for a set of fields.
   *
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   List of fields to display.
   *
   * @return array
   *   The build structure.
   */
  protected function buildFieldsTable(array $fields) {
      
    $types = $this->entity->supportedtypes();
    $_fields = $this->entity->getEngineFields();
    $build = [
      '#type' => 'details',
      '#open' => TRUE,
      '#theme' => 'elastic_appsearch_admin_fields_table',
      '#parents' => [],
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        $this->t('Appears In'),
        $this->t('Schema'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 2,
        ],
      ],
    ];

    ksort($fields);
    
    foreach ($fields as $key => $field) {      
      $build['fields'][$key]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $field['field']->getLabel() ?: $key,
        '#required' => TRUE,
        '#attributes' => ['readonly' => 'readonly'],
        '#size' => 40,
      ];
      $build['fields'][$key]['id'] = [
        '#type' => 'textfield',
        '#default_value' => $key,
        '#attributes' => ['readonly' => 'readonly'],
        '#required' => TRUE,
        '#size' => 35,
      ];
      
      $build['fields'][$key]['appears_in'] = [
        '#markup' => Html::escape(implode(', ',$field['appears_in'])),
      ];

      $build['fields'][$key]['type'] = [
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => (isset($_fields[$key])) ? $_fields[$key]['type'] : 'text',
      ];

      $build['fields'][$key]['enable'] = [
        '#type' => 'checkbox',
        '#default_value' => (isset($_fields[$key])) ? 1 : 0,
      ];
    
    }

    return $build;
  }

  public function formatSchema($fields, $json = false){
    $schema = [];
    foreach($fields as $field){
      if(isset($field['enable']) && $field['enable'] == 1){
        if($json){
          $schema[$field['id']] = $field['type'];
        }else{
          $schema[] = [
            'label' => $field['title'],
            'field_id' => $field['id'],
            'type' => $field['type']
          ];
        } 
      }
    }
    return $schema;
  }

  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $engine = $this->entity;

    $field_values = $form_state->getValue('fields', []);

    $engine->setSchema($this->formatSchema($field_values));

    $status = $engine->save();
    
    if($status){
      $engine->setItemsTrackable();
      if($engine->getServerInstance()->isAvailable()){
        $engine->getClient()->updateSchema($engine->id(),$this->formatSchema($field_values, true));
      }
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Engine.', [
          '%label' => $engine->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Engine.', [
          '%label' => $engine->label(),
        ]));
    }
    $form_state->setRedirectUrl($engine->toUrl('canonical'));
  }

  /**
   * Cancels the editing of the index's fields.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    if ($this->entity instanceof UnsavedConfigurationInterface && $this->entity->hasChanges()) {
      $this->entity->discardChanges();
    }

    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
  }

}
