<?php

namespace Drupal\local_contexts_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Local Contexts' block.
 *
 * @Block(
 *   id = "local_contexts_block",
 *   admin_label = @Translation("Local Contexts Block")
 * )
 */

class LocalContextsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('This is a simple Local Contexts block!'),
    ];
  }

}
