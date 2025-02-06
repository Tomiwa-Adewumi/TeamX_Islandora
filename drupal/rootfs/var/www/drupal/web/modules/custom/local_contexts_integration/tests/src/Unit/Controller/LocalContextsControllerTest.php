<?php

declare(strict_types=1);


namespace Drupal\Tests\local_contexts_integration\Unit\Controller;

use Drupal\local_contexts_integration\Controller\LocalContextsController;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Unit tests for LocalContextsController.
 *
 * @group local_contexts_integration
 */
class LocalContextsControllerTest extends TestCase {
  
  protected $httpClient;
  protected $routeMatch;
  protected $cache;
  protected $controller;

  protected function setUp(): void {
    parent::setUp();
    
    // Mock dependencies
    $this->httpClient = $this->createMock(Client::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    
    // Instantiate the controller with mocked dependencies
    $this->controller = new LocalContextsController($this->httpClient, $this->routeMatch, $this->cache);
  }
  
  /**
   * Tests fetching project data from a node.
   */
  public function testFetchProjectDataFromNode(): void {
    $node = $this->createMock(Node::class);
    $node->method('id')->willReturn(123);
    $node->method('hasField')->with('field_identifier')->willReturn(true);
    $node->method('get')->with('field_identifier')->willReturn((object) ['value' => 'project_123']);

    $this->cache->method('get')->with('local_contexts_project:project_123')->willReturn(FALSE);
    
    $mockResponseData = [
      'unique_id' => '123',
      'title' => 'Test Project',
      'date_added' => '2024-01-01',
      'date_modified' => '2024-02-01',
      'tk_labels' => ['Label1', 'Label2'],
    ];

    $this->httpClient
      ->method('get')
      ->willReturn(new Response(200, [], json_encode($mockResponseData)));

    $result = $this->controller->fetchProjectDataFromNode($node);

    $this->assertSame($mockResponseData, $result);
  }

  /**
   * Tests caching mechanism.
   */
  public function testFetchProjectDataFromCache(): void {
    $node = $this->createMock(Node::class);
    $node->method('id')->willReturn(123);
    $node->method('hasField')->with('field_identifier')->willReturn(true);
    $node->method('get')->with('field_identifier')->willReturn((object) ['value' => 'project_123']);

    $cachedData = ['title' => 'Cached Project', 'unique_id' => '123'];
    $this->cache->method('get')->with('local_contexts_project:project_123')->willReturn((object) ['data' => $cachedData]);
    
    $result = $this->controller->fetchProjectDataFromNode($node);

    $this->assertSame($cachedData, $result);
  }

  /**
   * Tests API failure handling.
   */
  public function testFetchProjectDataApiFailure(): void {
    $node = $this->createMock(Node::class);
    $node->method('id')->willReturn(123);
    $node->method('hasField')->with('field_identifier')->willReturn(true);
    $node->method('get')->with('field_identifier')->willReturn((object) ['value' => 'project_123']);

    $this->cache->method('get')->with('local_contexts_project:project_123')->willReturn(FALSE);

    $this->httpClient->method('get')->willThrowException(new RequestException('API error', $this->createMock(RequestInterface::class)));

    $result = $this->controller->fetchProjectDataFromNode($node);

    $this->assertSame([], $result);
  }
  
  /**
   * Tests filterApiResponse() to ensure it filters correctly.
   */
  public function testFilterApiResponse(): void {
    $rawData = [
      'unique_id' => '456',
      'title' => 'Filtered Project',
      'date_added' => '2024-03-01',
      'date_modified' => '2024-04-01',
      'tk_labels' => ['LabelA', 'LabelB'],
      'extra_field' => 'Should be removed',
    ];

    $expectedData = [
      'unique_id' => '456',
      'title' => 'Filtered Project',
      'date_added' => '2024-03-01',
      'date_modified' => '2024-04-01',
      'tk_labels' => ['LabelA', 'LabelB'],
    ];

    $filteredData = $this->controller->filterApiResponse($rawData);

    $this->assertSame($expectedData, $filteredData);
  }

  /**
   * Tests the performApiCall() method to verify API interaction.
   */
  public function testPerformApiCall(): void {
    $mockResponseData = [
      'unique_id' => '789',
      'title' => 'API Test Project',
      'date_added' => '2024-05-01',
      'date_modified' => '2024-06-01',
      'tk_labels' => ['TestLabel'],
    ];

    $this->httpClient
      ->method('get')
      ->willReturn(new Response(200, [], json_encode($mockResponseData)));
    
    $result = $this->controller->performApiCall('project_789');
    
    $this->assertSame($mockResponseData, $result);
  }
}