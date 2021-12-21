<?php

declare(strict_types=1);

namespace Drupal\contrib_tracker;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class to calcluate contribution statistics.
 */
class ContributionStatistics {

  /**
   * Node storage controller.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Contribution storage constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
  }

  /**
   * Calcuates total contributions.
   */
  public function totalContributions(): int {
    $query = $this->nodeStorage->getQuery();
    $nids = $query->condition('type', 'code_contribution')
      ->condition('status', '1')
      ->execute();
    return count($nids);
  }

  /**
   * Calcuate total contributions with patches.
   */
  public function contributionWithPatches(): int {
    $query = $this->nodeStorage->getQuery();
    $nids = $query->condition('type', 'code_contribution')
      ->condition('field_code_contrib_patches_count', 0, '!=')
      ->condition('status', '1')
      ->execute();
    return count($nids);
  }

  /**
   * Calcuate total number of patches.
   */
  public function totalPatches(): int {
    $query = $this->nodeStorage->getQuery();
    $nids = $query->condition('type', 'code_contribution')
      ->condition('field_code_contrib_patches_count', 0, '!=')
      ->condition('status', '1')
      ->execute();
    return count($nids);
  }

}
