<?php

declare(strict_types=1);

namespace Drupal\ct_reports;

use Drupal\Core\Database\Connection;
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
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Contribution storage constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->database = $database;
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
  public function codeContributions(): int {
    $query = $this->nodeStorage->getQuery();
    $nids = $query->condition('type', 'code_contribution')
      ->condition('field_code_contrib_patches_count', 0, '!=')
      ->condition('status', '1')
      ->execute();
    return count($nids);
  }

  /**
   * Calcuate total number of contributors.
   */
  public function totalContributors(): int {
    $query = $this->database->select('node__field_contribution_author', 'ca');
    $query->fields('ca', ['field_contribution_author_target_id']);
    $result = $query->distinct()->execute()->fetchAll();

    return count($result);
  }

}
