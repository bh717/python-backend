<?php

namespace Drupal\contrib_tracker;

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
   * @param \Drupal\node\NodeInterface $issue_node
   *   The issue node this comment belongs to.
   * @param \Drupal\taxonomy\TermInterface $project_term
   *   The project term this comment belongs to.
   * @param \Drupal\user\UserInterface $user
   *   The user contributing the comment.
   * @param int $patch_files
   *   Number of patches in the comment.
   * @param int $total_files
   *   Number of files in the comment.
   * @param string $status
   *   Issue status at the time of this comment.
   *
   * @return \Drupal\node\NodeInterface
   *   The comment node created based on passed in data.
   */
  public function saveIssueComment(DrupalOrgComment $comment, NodeInterface $issue_node, TermInterface $project_term, UserInterface $user, $patch_files, $total_files, $status);

  /**
   * Save issue.
   *
   * @param \Hussainweb\DrupalApi\Entity\Node $issue_data
   *   The issue data.
   * @param \Drupal\user\UserInterface $user
   *   The user contributing the comment belonging to this issue.
   *
   * @return \Drupal\node\NodeInterface
   *   The node created based on passed in data.
   */
  public function saveIssue(DrupalOrgNode $issue_data, UserInterface $user);

  /**
   * Get node saved in the system for the given drupal.org issue.
   *
   * @param string $issue_link
   *   The drupal.org link to the issue.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node with the issue link or NULL if not found.
   */
  public function getNodeForDrupalOrgIssue($issue_link);

  /**
   * Get node saved in the system for the given drupal.org issue comment.
   *
   * @param string $comment_link
   *   The drupal.org link to the comment in the issue.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node with the issue comment link or NULL if not found.
   */
  public function getNodeForDrupalOrgIssueComment($comment_link);

  /**
   * Get (or create) a project term.
   *
   * @param string $project_name
   *   The name of the project.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term from the project vocabulary with the given name.
   */
  public function getProjectTerm($project_name);

}
