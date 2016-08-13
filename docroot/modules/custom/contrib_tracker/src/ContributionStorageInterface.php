<?php

namespace Drupal\contrib_tracker;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;

interface ContributionStorageInterface {

  public function saveIssueComment(DrupalOrgComment $comment, NodeInterface $issue_node, TermInterface $project_term, UserInterface $user);

  public function saveIssue(DrupalOrgNode $issue_data, UserInterface $user);

  public function getNodeForDrupalOrgIssue($issue_link);

  public function getNodeForDrupalOrgIssueComment($comment_link);

  public function getProjectTerm($project_name);

}
