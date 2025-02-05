<?php

namespace Drupal\Tests\local_contexts_integration\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\local_contexts_integration\Plugin\Block\LocalContextsBlock;
use Drupal\local_contexts_integration\Controller\LocalContextsController;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the LocalContextsBlock class.
 *
 * @group local_contexts_integration
 */
class LocalContextsBlockUnitTest extends UnitTestCase {
    use ProphecyTrait;

    protected $localContextsController;

    protected function setUp(): void {
        parent::setUp();
        $this->localContextsController = $this->prophesize(LocalContextsController::class);
    }

    public function testBuildWithCompleteData() {
        $mockData = [
            'unique_id' => '12345',
            'title' => 'Test Project',
            'date_added' => '2024-01-01',
            'date_modified' => '2024-01-15',
            'tk_labels' => ['Label 1', 'Label 2'],
        ];

        $this->localContextsController->fetchProjectData()->willReturn($mockData);

        $block = new LocalContextsBlock(['provider' => 'custom'], 'local_contexts_block', [], $this->localContextsController->reveal());
        $build = $block->build();

        $this->assertArrayHasKey('#tk_labels', $build);
        $this->assertNotEmpty($build['#tk_labels']);
    }

    public function testBuildWithMissingData() {
        $mockData = [];

        $this->localContextsController->fetchProjectData()->willReturn($mockData);

        $block = new LocalContextsBlock(['provider' => 'custom'], 'local_contexts_block', [], $this->localContextsController->reveal());
        $build = $block->build();

        $this->assertArrayHasKey('#tk_labels', $build);
        $this->assertEmpty($build['#tk_labels']);
    }

    public function testGetCacheMaxAge() {
        $block = new LocalContextsBlock(['provider' => 'custom'], 'local_contexts_block', [], $this->localContextsController->reveal());
        $this->assertEquals(0, $block->getCacheMaxAge());
    }

    public function testBuildWithInvalidApiResponse() {
        $this->localContextsController->fetchProjectData()->willReturn(null);

        $block = new LocalContextsBlock(['provider' => 'custom'], 'local_contexts_block', [], $this->localContextsController->reveal());
        $build = $block->build();

        $this->assertArrayHasKey('#tk_labels', $build);
        $this->assertEmpty($build['#tk_labels']);
    }
}

// forever testing fire
