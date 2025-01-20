<?php

namespace Drupal\local_contexts_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\local_contexts_integration\Controller\LocalContextsController;

/**
 * Provides a Local Contexts Data Block.
 *
 * @Block(
 *   id = "local_contexts_block",
 *   admin_label = @Translation("Local Contexts Block"),
 *   category = @Translation("Custom"),
 * )
 */
class LocalContextsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Local Contexts controller.
   *
   * @var \Drupal\local_contexts_integration\Controller\LocalContextsController
   */
  protected $localContextsController;

  /**
   * Constructs a new LocalContextsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\local_contexts_integration\Controller\LocalContextsController $localContextsController
   *   The Local Contexts controller.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocalContextsController $localContextsController) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->localContextsController = $localContextsController;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('local_contexts_integration.controller')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Fetch data from the Local Contexts controller.
    $data = $this->localContextsController->fetchProjectData();

    // Shorten the data for display.
    $output = '<p>No data available.</p>';
    if ($data) {
      $short_data = json_encode($data);
      if (strlen($short_data) > 500) {
        $short_data = substr($short_data, 0, 500) . '...';
      }
      $content = $short_data;
    }
  
    // Render the block using a Twig template.
    return [
      '#theme' => 'local_contexts_block',
      '#title' => $this->t('Local Contexts Project Data'),
      '#content' => $content,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
