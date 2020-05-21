<?php

namespace Drupal\ct_manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class implementation of the storage utility.
 */
class ContributionTrackerStorage {
  /**
   * Node storage controller.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Logger.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $logger;

  /**
   * Contribution storage constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelInterface $loggerChannel) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->logger = $loggerChannel;
  }

  /**
   * Store issue and comments of user.
   */
  public function storeData($userContribtions) {
    $issues = $userContribtions['issueComments'];
    $issueComments = $userContribtions['issueComments'];
    $issues ? $this->saveIssue($issues) : [];
    $issueComments ? $this->saveIssueComments($issueComments) : [];
  }

  /**
   * Save Issues.
   */
  public function saveIssue($issues) {
    foreach ($issues as $value) {
      $node = $this->nodeStorage->create($value);
      $node_saved = $node->save();
      if ($node_saved) {
        $this->logger->notice('Node for Issue is Created with node id : @nid', ['@nid' => $node->id()]);
      }
    }

  }

  /**
   * Save IssueComments.
   */
  public function saveIssueComments($issueComments) {
    foreach ($issueComments as $value) {
      $node = $this->nodeStorage->create($value);
      $node_saved = $node->save();
      if ($node_saved) {
        $this->logger->notice('Node For IssueComments is Created with node id : @nid', ['@nid' => $node->id()]);
      }
    }

  }

}
