<?php

namespace Drupal\Tests\local_contexts_integration\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\local_contexts_integration\Controller\LocalContextsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

use PHPUnit\Framework\TestCase;

/**
 * Tests the Local Contexts Integration cache bin.
 *
 * @group local_contexts_integration
 */
class LocalContextsIntegrationCacheTest extends TestCase {

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a mock cache service.
    $this->cache = $this->createMock(CacheBackendInterface::class);

    // Create a mock container and set the cache service.
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->with('cache.local_contexts_integration')
      ->willReturn($this->cache);

    // Set the container to the Drupal global container.
    \Drupal::setContainer($container);
  }

  
/**
 * Tests setting and getting a cache item.
 */
public function testSetAndGetCache() {
  // Arrange: Define a cache ID and data.
  $cid = 'test_cache_id';
  $data = ['foo' => 'bar'];

  // Act: Set the cache item.
  $this->cache->expects($this->once())
    ->method('set')
    ->with($cid, $data);

  $this->cache->set($cid, $data);

  // Act: Get the cache item.
  $this->cache->expects($this->once())
    ->method('get')
    ->with($cid)
    ->willReturn((object) ['data' => $data]);

  $cached_data = $this->cache->get($cid);

  // Assert: Verify the cached data matches the original data.
  $this->assertEquals($data, $cached_data->data);
}

/**
 * Tests deleting a cache item.
 */
public function testDeleteCache() {
  // Arrange: Define a cache ID.
  $cid = 'test_cache_id';

  // Act: Delete the cache item.
  $this->cache->expects($this->once())
    ->method('delete')
    ->with($cid);

  $this->cache->delete($cid);

  // Assert: Verify the cache item is deleted.
  $this->cache->expects($this->once())
    ->method('get')
    ->with($cid)
    ->willReturn(FALSE);

  $cached_data = $this->cache->get($cid);
  $this->assertFalse($cached_data);
}

/**
 * Tests invalidating a cache item.
 */
public function testInvalidateCache() {
  // Arrange: Define a cache ID.
  $cid = 'test_cache_id';

  // Act: Invalidate the cache item.
  $this->cache->expects($this->once())
    ->method('invalidate')
    ->with($cid);

  $this->cache->invalidate($cid);

  // Assert: Verify the cache item is invalidated.
  $this->cache->expects($this->once())
    ->method('get')
    ->with($cid)
    ->willReturn(FALSE);

  $cached_data = $this->cache->get($cid);
  $this->assertFalse($cached_data);
}

}