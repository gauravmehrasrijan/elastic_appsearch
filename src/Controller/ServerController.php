<?php

namespace Drupal\elastic_appsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\elastic_appsearch\Entity\ServerInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class EngineController.
 */
class ServerController extends ControllerBase {

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
   * @param \Drupal\elastic_appsearch\ServerInterface $elastic_appsearch_server
   *   The index that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(ServerInterface $elastic_appsearch_server) {
    return new FormattableMarkup('@title', ['@title' => $elastic_appsearch_server->label()]);
  }

  /**
   * Displays information about a engine.
   *
   * @param \Drupal\elastic_appsearch\ServerInterface $elastic_appsearch_server
   *   The index to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(ServerInterface $elastic_appsearch_server) {
    // Build the search index information.
    $render = [
      'view' => [
        '#theme' => 'server',
        '#server' => $elastic_appsearch_server,
      ],
    ];

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function schema($elastic_appsearch_engine) {

    return [
      'view' => [
        '#theme' => 'table',
        '#engine' => $elastic_appsearch_engine,
      ],
    ];
  }

}
