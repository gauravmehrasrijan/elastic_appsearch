<?php

namespace Drupal\elastic_appsearch\Tests;

use Drupal\simpletest\WebTestBase;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Provides automated tests for the elastic_appsearch module.
 */
class EngineControllerTest extends WebTestBase {

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
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "elastic_appsearch EngineController's controller functionality",
      'description' => 'Test Unit for module elastic_appsearch and controller EngineController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests elastic_appsearch functionality.
   */
  public function testEngineController() {
    // Check that the basic functions of module elastic_appsearch.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
