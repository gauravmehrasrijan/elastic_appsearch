<?php

namespace Drupal\elastic_appsearch;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use GuzzleHttp\Exception\RequestException;
use Elastic\AppSearch\Client\ClientBuilder;
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
  protected $client;

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
    $this->httpClient = $http_client;    
    $this->config = $config->get('elastic_appsearch.appsearch');
    $this->server = $this->config->get('api_endpoint');
    $this->apikey = $this->config->get('api_private_key');
    $this->engineName = $this->config->get('engine_name');
    
    if(!empty($this->server)){
      $this->connect($this->server, $this->apikey, $this->engineName);
    }

  }

  public static function connect($server, $apikey, $engine){
    $clientBuilder = ClientBuilder::create($server, $apikey );
    $client = $clientBuilder->build();
    $engine = $client->getEngine($engine);
  }


  public function setEngine($engine){
    $this->engine = $this->client->getEngine($engine);
    return this;
  }

}
