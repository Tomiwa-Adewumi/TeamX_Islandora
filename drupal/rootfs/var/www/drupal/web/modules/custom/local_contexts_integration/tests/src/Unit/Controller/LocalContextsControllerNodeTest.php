<?php

namespace Drupal\Tests\local_contexts_integration\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\local_contexts_integration\Controller\LocalContextsController;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use GuzzleHttp\Client;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Psr\Log\LoggerInterface;

use PHPUnit\Framework\TestCase;

/**
 * Tests for LocalContextsController.
 *
 * @group local_contexts_integration
 */
class LocalContextsControllerNodeTest extends TestCase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The LocalContextsController instance.
   *
   * @var \Drupal\local_contexts_integration\Controller\LocalContextsController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the HTTP client.
    $this->httpClient = $this->createMock(Client::class);

    // Mock the Route Match service.
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);

    // Mock the cache backend.
    $this->cache = $this->createMock(CacheBackendInterface::class);

    // Mock the logger service.
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock the config factory.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    // Create a container and set the services.
    $container = new ContainerBuilder();
    $container->set('http_client', $this->httpClient);
    $container->set('current_route_match', $this->routeMatch);
    $container->set('cache.local_contexts_integration', $this->cache);
    $container->set('logger.factory', $this->logger);
    $container->set('config.factory', $this->configFactory);
    \Drupal::setContainer($container);

    // Instantiate the controller.
    $this->controller = new LocalContextsController($this->httpClient, $this->routeMatch, $this->cache);
  }

  /**
   * Tests the fetchProjectData method when no node is found.
   */
  public function testFetchProjectDataNoNode() {
    // Set up the route match to return NULL for the node parameter.
    $this->routeMatch->method('getParameter')->with('node')->willReturn(NULL);

    // Expect a warning log message.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with('No node found in the current route.');

    // Call the method and assert the result is an empty array.
    $result = $this->controller->fetchProjectData();
    $this->assertEquals([], $result);
  }

  /**
   * Tests the fetchProjectDataFromNode method with a valid node.
   */
  public function testFetchProjectDataFromNode() {
    // Mock a node entity.
    $node = $this->createMock(Node::class);

    // Mock the config to return a field identifier.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('field_identifier')->willReturn('field_project_id');
    $this->configFactory->method('get')->with('local_contexts_integration.settings')->willReturn($config);

    // Mock the node to return a project ID.
    $node->method('hasField')->with('field_project_id')->willReturn(TRUE);
    $node->method('get')->with('field_project_id')->willReturnSelf();
    $node->method('isEmpty')->willReturn(FALSE);
    $node->method('value')->willReturn('12345');

    // Mock the cache to return a cache miss.
    $this->cache->method('get')->willReturn(FALSE);

    // Mock the API call to return some data.
    $this->httpClient->method('get')->willReturnCallback(function () {
      $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
      $response->method('getBody')->willReturn(json_encode([
        'unique_id' => '12345',
        'title' => 'Test Project',
        'date_added' => '2023-01-01',
        'date_modified' => '2023-01-02',
        'tk_labels' => [],
      ]));
      return $response;
    });

    // Expect the cache to be set.
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->equalTo('local_contexts_project:12345'), $this->isType('array'), $this->isType('int'), $this->equalTo(['local_contexts_project']));

    // Call the method and assert the result.
    $result = $this->controller->fetchProjectDataFromNode($node);
    $this->assertEquals([
      'unique_id' => '12345',
      'title' => 'Test Project',
      'date_added' => '2023-01-01',
      'date_modified' => '2023-01-02',
      'tk_labels' => [],
    ], $result);
  }

  /**
   * Tests the fetchProjectDataFromNode method with a cache hit.
   */
  public function testFetchProjectDataFromNodeCacheHit() {
    // Mock a node entity.
    $node = $this->createMock(Node::class);

    // Mock the config to return a field identifier.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('field_identifier')->willReturn('field_project_id');
    $this->configFactory->method('get')->with('local_contexts_integration.settings')->willReturn($config);

    // Mock the node to return a project ID.
    $node->method('hasField')->with('field_project_id')->willReturn(TRUE);
    $node->method('get')->with('field_project_id')->willReturnSelf();
    $node->method('isEmpty')->willReturn(FALSE);
    $node->method('value')->willReturn('12345');

    // Mock the cache to return a cache hit.
    $cache_item = (object) ['data' => [
      'unique_id' => '12345',
      'title' => 'Cached Project',
      'date_added' => '2023-01-01',
      'date_modified' => '2023-01-02',
      'tk_labels' => [],
    ]];
    $this->cache->method('get')->willReturn($cache_item);

    // Expect a cache hit log message.
    $this->logger->expects($this->once())
      ->method('notice')
      ->with('Cache HIT for Project ID: 12345');

    // Call the method and assert the result.
    $result = $this->controller->fetchProjectDataFromNode($node);
    $this->assertEquals([
      'unique_id' => '12345',
      'title' => 'Cached Project',
      'date_added' => '2023-01-01',
      'date_modified' => '2023-01-02',
      'tk_labels' => [],
    ], $result);
  }

  /**
   * Tests the fetchProjectDataFromNode method with an empty API response.
   */
  public function testFetchProjectDataFromNodeEmptyApiResponse() {
    // Mock a node entity.
    $node = $this->createMock(Node::class);

    // Mock the config to return a field identifier.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('field_identifier')->willReturn('field_project_id');
    $this->configFactory->method('get')->with('local_contexts_integration.settings')->willReturn($config);

    // Mock the node to return a project ID.
    $node->method('hasField')->with('field_project_id')->willReturn(TRUE);
    $node->method('get')->with('field_project_id')->willReturnSelf();
    $node->method('isEmpty')->willReturn(FALSE);
    $node->method('value')->willReturn('12345');

    // Mock the cache to return a cache miss.
    $this->cache->method('get')->willReturn(FALSE);

    // Mock the API call to return an empty response.
    $this->httpClient->method('get')->willReturnCallback(function () {
      $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
      $response->method('getBody')->willReturn(json_encode([]));
      return $response;
    });

    // Expect a warning log message.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with('API returned empty or invalid data for Project ID: 12345');

    // Call the method and assert the result is an empty array.
    $result = $this->controller->fetchProjectDataFromNode($node);
    $this->assertEquals([], $result);
  }

  /**
   * Tests the fetchProjectDataFromNode method with an invalid node.
   */
  public function testFetchProjectDataFromNodeInvalidNode() {
    // Mock a node entity.
    $node = $this->createMock(Node::class);

    // Mock the config to return a field identifier.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('field_identifier')->willReturn('field_project_id');
    $this->configFactory->method('get')->with('local_contexts_integration.settings')->willReturn($config);

    // Mock the node to return a project ID.
    $node->method('hasField')->with('field_project_id')->willReturn(FALSE);

    // Expect a warning log message.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with("Field 'field_project_id' is missing or empty on node ID: 12345");

    // Call the method and assert the result is an empty array.
    $result = $this->controller->fetchProjectDataFromNode($node);
    $this->assertEquals([], $result);
  }

}