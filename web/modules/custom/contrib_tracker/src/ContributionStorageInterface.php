<?php

namespace Drupal\contrib_tracker;

use Drupal\contrib_tracker\DrupalOrg\CommentDetails;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;

/**
 * Interface for Contribution Storage service.
 */
interface ContributionStorageInterface {

  /**
   * Save issue comment.
   *
   * @param \Hussainweb\DrupalApi\Entity\Comment $comment
   *   The comment data from drupal.org.
   * @param \Drupal\contrib_tracker\DrupalOrg\CommentDetails $commentDetails
   *   The issue node this comment belongs to.
   * @param \Drupal\node\NodeInterface $issueNode
   *   The issue node this comment belongs to.
   * @param \Drupal\taxonomy\TermInterface $projectTerm
   *   The project term this comment belongs to.
   * @param \Drupal\user\UserInterface $user
   *   The user contributing the comment.
   *
   * @return \Drupal\node\NodeInterface
   *   The comment node created based on passed in data.
   */
  public function saveIssueComment(DrupalOrgComment $comment, CommentDetails $commentDetails, NodeInterface $issueNode, TermInterface $projectTerm, UserInterface $user);

  /**
   * Save issue.
   *
   * @param \Hussainweb\DrupalApi\Entity\Node $issueData
   *   The issue data.
   * @param \Drupal\user\UserInterface $user
   *   The user contributing the comment belonging to this issue.
   *
   * @return \Drupal\node\NodeInterface
   *   The node created based on passed in data.
   */
  public function saveIssue(DrupalOrgNode $issueData, UserInterface $user);

  /**
   * Get node saved in the system for the given drupal.org issue.
   *
   * @param string $issueLink
   *   The drupal.org link to the issue.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node with the issue link or NULL if not found.
   */
  public function getNodeForDrupalOrgIssue($issueLink);

  /**
   * Get node saved in the system for the given drupal.org issue comment.
   *
   * @param string $commentLink
   *   The drupal.org link to the comment in the issue.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node with the issue comment link or NULL if not found.
   */
  public function getNodeForDrupalOrgIssueComment($commentLink);

  /**
   * Get (or create) a project term.
   *
   * @param string $projectName
   *   The name of the project.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term from the project vocabulary with the given name.
   */
  public function getProjectTerm($projectName);

}
