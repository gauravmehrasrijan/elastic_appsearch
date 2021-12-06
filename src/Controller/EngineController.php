<?php

namespace Drupal\elastic_appsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\elastic_appsearch\Entity\EngineInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class Engine Controller.
 */
class EngineController extends ControllerBase {

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
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queue;

  /**
   * Constructs a new EngineController object.
   */
  public function __construct(ClientInterface $http_client, Connection $database, ContainerAwareInterface $queue) {
    $this->httpClient = $http_client;
    $this->database = $database;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('database'),
      $container->get('queue')
    );
  }

  /**
   * Returns the page title for an engine's "View" tab.
   *
   * @param \Drupal\search_api\EngineInterface $elastic_appsearch_engine
   *   The index that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(EngineInterface $elastic_appsearch_engine) {
    return new FormattableMarkup('@title', ['@title' => $elastic_appsearch_engine->label()]);
  }

  /**
   * Displays information about a engine.
   *
   * @param \Drupal\elastic_appsearch\EngineInterface $elastic_appsearch_engine
   *   The index to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(EngineInterface $elastic_appsearch_engine) {
    // Build the search index information.
    $render = [
      'view' => [
        '#theme' => 'engine',
        '#engine' => $elastic_appsearch_engine,
      ],
    ];

    if (
      $elastic_appsearch_engine->status() && $elastic_appsearch_engine->getServerInstance()->isAvailable()) {
      // Attach the index status form.
      $render['form'] = $this->formBuilder()->getForm('Drupal\elastic_appsearch\Form\EngineStatusForm', $elastic_appsearch_engine);
    }
    return $render;
  }

}
