<?php

namespace Drupal\elastic_appsearch;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\elastic_appsearch\Entity\ServerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\elastic_appsearch\Entity\EngineInterface;

/**
 * Provides a listing of Server entities.
 */
class ServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'type' => $this->t('Type'),
      'title' => $this->t('Name'),
      'status' => $this->t('Status'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $row = parent::buildRow($entity);

    $status = $entity->status();
    $row = [
      'data' => [
        'type' => [
          'data' => $entity instanceof ServerInterface ? $this->t('Server') : $this->t('Engine'),
          'class' => ['search-api-type'],
        ],
        'title' => [
          'data' => [
            '#type' => 'link',
            '#title' => $entity->label(),
          ] + $entity->toUrl('canonical')->toRenderArray(),
          'class' => ['search-api-title'],
        ],
        'status' => [
          'data' => $status ? $this->t('Enabled') : $this->t('Disabled'),
          'class' => ['search-api-status'],
        ],
        'operations' => $row['operations'],
      ],
      'title' => $this->t('ID: @name', ['@name' => $entity->id()]),
      'class' => [
        Html::cleanCssIdentifier($entity->getEntityTypeId() . '-' . $entity->id()),
        $status ? 'search-api-list-enabled' : 'search-api-list-disabled',
        $entity instanceof ServerInterface ? 'search-api-list-server' : 'search-api-list-index',
      ],
    ];

    $description = $entity->get('description');
    if ($description) {
      $row['data']['title']['data']['#suffix'] = '<div class="description">' . $description . '</div>';
    }

    if ($status
      && $entity instanceof ServerInterface
      && !$entity->isAvailable()
    ) {
      $row['data']['status']['data'] = $this->t('Unavailable');
      $row['class'][] = 'color-error';
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $entity_groups = $this->loadGroups();

    $list['#type'] = 'container';
    $list['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    $list['servers'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '',
      '#attributes' => [
        'id' => 'search-api-entity-list',
        'class' => [
          'search-api-entity-list',
          'search-api-entity-list--servers-with-indexes',
        ],
      ],
    ];
    foreach ($entity_groups['servers'] as $server_groups) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
      foreach ($server_groups as $entity) {
        $list['servers']['#rows'][$entity->getEntityTypeId() . '.' . $entity->id()] = $this->buildRow($entity);
      }
    }

    return $list;
  }

  /**
   * Loads search servers and engines, grouped by servers.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[][]
   *   An associative array with two keys:
   *   - servers: All available search servers, each followed by all search
   *     indexes attached to it.
   *   - lone_indexes: All search indexes that aren't attached to any server.
   */
  public function loadGroups() {
    $servers = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_server')->loadMultiple();
    /** @var \Drupal\search_api\ServerInterface[] $servers */
    $engines = \Drupal::entityTypeManager()->getStorage('elastic_appsearch_engine')->loadMultiple();

    $server_groups = [];
    foreach ($servers as $server) {
      $server_group = [
        'elastic_appsearch_server.' . $server->id() => $server,
      ];

      foreach ($server->getEngines() as $engine) {
        $server_group['elastic_appsearch_engine.' . $engine->id()] = $engine;
        unset($engines[$engine->id()]);
      }

      $server_groups['elastic_appsearch_server.' . $server->id()] = $server_group;
    }

    return [
      'servers' => $server_groups
    ];
  }

  /**
   * Sorts an array of entities by status and then alphabetically.
   *
   * Will preserve the key/value association of the array.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $entities
   *   An array of config entities.
   */
  protected function sortByStatusThenAlphabetically(array &$entities) {
    uasort($entities, function (ConfigEntityInterface $a, ConfigEntityInterface $b) {
      if ($a->status() == $b->status()) {
        return strnatcasecmp($a->label(), $b->label());
      }
      else {
        return $a->status() ? -1 : 1;
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity instanceof ServerInterface) {
      $route_parameters['elastic_appsearch_server'] = $entity->id();
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 20,
        'url' => new Url('elastic_appsearch.elastic_appsearch_server.canonical', $route_parameters),
      ];
    }

    if ($entity instanceof EngineInterface) {
      $route_parameters['elastic_appsearch_engine'] = $entity->id();
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 20,
        'url' => new Url('elastic_appsearch.elastic_appsearch_engine.canonical', $route_parameters),
      ];

      $operations['schema'] = [
        'title' => $this->t('Field Schema'),
        'weight' => 21,
        'url' => new Url(
          'entity.elastic_appsearch_engine.schema', $route_parameters),
      ];

      $operations['referenceui'] = [
        'title' => $this->t('Search UI'),
        'weight' => 21,
        'url' => new Url(
          'entity.elastic_appsearch_referenceui.collection', $route_parameters),
      ];
    }

    return $operations;
  }

}
