<?php

namespace Drupal\elastic_appsearch;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use GuzzleHttp\Exception\RequestException;
use Elastic\AppSearch\Client\ClientBuilder;
use Drupal\elastic_appsearch\Entity\ServerInterface;

/**
 * Class AppSearchClient.
 */
class ElasticAppSearchClient implements ElasticAppsearchClientInterface {

  use LoggerChannelTrait;
  use MessengerTrait;

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client
   */
  public $client;

  /**
   * Drupal\webprofiler\Config\ConfigFactoryWrapper definition.
   *
   * @var \Drupal\webprofiler\Config\ConfigFactoryWrapper
   */
  protected $configFactory;

  /**
   * Constructs a new AppSearchClient object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config) {

  }

  /**
   * {@inheritdoc}
   */
  public function getInstance($server) {

    if (!empty($this->client[$server->id()])) {
      return $this->client[$server->id()];
    }

    $clientBuilder = ClientBuilder::create(
      $server->getHost(),
      $server->getSecret()
    );

    $this->client[$server->id()] = $clientBuilder->build();

    return $this->client[$server->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function connect($server, $apikey, $engine = FALSE) {
    $clientBuilder = ClientBuilder::create($server, $apikey);
    $client = $clientBuilder->build();
    if ($engine) {
      $engine = $client->getEngine($engine);
      return $client;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEngine($engine) {
    $this->engine = $this->client->getEngine($engine);
    return this;
  }

}
