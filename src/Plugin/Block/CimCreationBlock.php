<?php

declare(strict_types=1);

namespace Drupal\authnet_cim_manager\Plugin\Block;

use Drupal\authnet_cim_manager\Form\CimCreationFom;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a cim creation block.
 *
 * @Block(
 *   id = "authnet_cim_manager_cim_creation",
 *   admin_label = @Translation("CIM Creation Form"),
 *   category = @Translation("Other"),
 * )
 */
final class CimCreationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return authnet_cim_manager_get_form(CimCreationFom::class);
  }
}
