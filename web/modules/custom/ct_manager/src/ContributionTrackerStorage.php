<?php

declare(strict_types=1);

namespace Drupal\ct_manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

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
   * Save Issues.
   */
  public function saveIssue(Issue $issue): Node {
    // Create an issue.
    $node = $this->nodeStorage->create([
      'type' => 'issue',
      'title' => $issue->getTitle() ?: '(not found)',
      'field_issue_link' => $issue->getUrl(),
      'body' => [
        'value' => $issue->getDescription(),
        'format' => 'basic_html',
      ],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Save code contributions.
   */
  public function saveCodeContribution(CodeContribution $comment, Node $issueNode, User $user): Node {
    $title = $comment->getTitle();
    $commentBody = $comment->getDescription();

    if (empty($title)) {
      $title = 'Comment on ' . $issueNode->getTitle();
      if (!empty($commentBody)) {
        $title = strip_tags($commentBody);
        if (strlen($title) > 80) {
          $title = substr($comment['title'], 0, 77) . '...';
        }
      }
    }

    $node = $this->nodeStorage->create([
      'type' => 'code_contribution',
      'title' => $title,
      'field_code_contrib_link' => $comment->getUrl(),
      'field_contribution_author' => $user->id(),
      'field_contribution_date' => $comment->getDate()->format('Y-m-d'),
      'field_contribution_description' => [
        'value' => $commentBody,
        'format' => 'basic_html',
      ],
      'field_code_contrib_issue_link' => $issueNode->id(),
      'field_code_contrib_project' => $this->getOrCreateTerm($comment->getProject(), 'project')->id(),
      'field_code_contrib_issue_status' => $comment->getStatus() ?: NULL,
      'field_contribution_technology' => $comment->getTechnology() ?: NULL,
      'field_code_contrib_files_count' => $comment->getFilesCount() ?: 0,
      'field_code_contrib_patches_count' => $comment->getPatchCount() ?: 0,
    ]);
    $node->save();
    return $node;
  }

  /**
   * Get issue of type issue.
   */
  public function getNodeForIssue(string $issueLink): ?Node {
    $issues = $this->nodeStorage->getQuery()
      ->condition('type', 'issue')
      ->condition('field_issue_link', $issueLink)
      ->execute();

    return (count($issues) > 0) ? $this->nodeStorage->load(reset($issues)) : NULL;
  }

  /**
   * Get node of type code_contribution.
   */
  public function getNodeForCodeContribution(string $commentLink): ?Node {
    $nodes = $this->nodeStorage->getQuery()
      ->condition('type', 'code_contribution')
      ->condition('field_code_contrib_link', $commentLink)
      ->execute();

    return (count($nodes) > 0) ? $this->nodeStorage->load(reset($nodes)) : NULL;
  }

  /**
   * Retrieves the node detail for issues.
   */
  public function getOrCreateIssueNode(Issue $issue): Node {
    $issueNode = $this->getNodeForIssue($issue->getUrl());
    if (!$issueNode) {
      $issueNode = $this->saveIssue($issue);
    }
    return $issueNode;
  }

  /**
   * Get (or create) a term in a specified vocabulary.
   *
   * @param string $termName
   *   Name of the term to be retrieved or created.
   * @param string $vocabulary
   *   Machine name of the vocabulary.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term with the given name in the given vocabulary.
   */
  protected function getOrCreateTerm(string $termName, string $vocabulary): Term {
    $terms = $this->termStorage->getQuery()
      ->condition('name', $termName)
      ->condition('vid', $vocabulary)
      ->execute();

    if (count($terms) == 0) {
      $term = $this->termStorage->create([
        'name' => $termName,
        'vid' => $vocabulary,
      ]);
      $term->save();
      return $term;
    }

    return $this->termStorage->load(reset($terms));
  }

}
