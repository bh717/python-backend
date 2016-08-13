<?php

namespace Drupal\contrib_tracker;

use Drupal\user\UserInterface;

/**
 * Contribution manager service class.
 *
 * This class holds logic to retrieve contribution information from drupal.org
 * API and store it using the storage service. This service may be used to
 * perform broad operations on a particular drupal.org user.
 */
class ContributionManager implements ContributionManagerInterface {

  /**
   * Contribution storage service.
   *
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
   */
  protected $contributionStorage;

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contributionRetriever;

  /**
   * ContributionManager constructor.
   *
   * @param \Drupal\contrib_tracker\ContributionStorageInterface $contribution_storage
   *   The injected contribution storage service.
   * @param \Drupal\contrib_tracker\ContributionRetrieverInterface $retriever
   *   The injected contribution retriever service.
   */
  public function __construct(ContributionStorageInterface $contribution_storage, ContributionRetrieverInterface $retriever) {
    $this->contributionStorage = $contribution_storage;
    $this->contributionRetriever = $retriever;
  }

  /**
   * {@inheritdoc}
   */
  public function storeCommentsByDrupalOrgUser($uid, UserInterface $user) {
    /** @var \Hussainweb\DrupalApi\Entity\Comment $comment */
    foreach ($this->contributionRetriever->getDrupalOrgCommentsByAuthor($uid) as $comment) {
      $nid = $comment->node->id;
      $link = sprintf("https://www.drupal.org/node/%s", $nid);
      $comment_link = sprintf("https://www.drupal.org/node/%s#comment-%s", $nid, $comment->getId());

      // If we have stored this comment, we have stored everything after it.
      if ($this->contributionStorage->getNodeForDrupalOrgIssueComment($comment_link)) {
        break;
      }

      // This is a new comment. Get the issue node first.
      $issue_data = $this->contributionRetriever->getDrupalOrgNode($nid, FALSE, REQUEST_TIME + 600);
      if (isset($issue_data->type) && $issue_data->type == 'project_issue') {
        $issue_node = $this->contributionStorage->getNodeForDrupalOrgIssue($link);
        if (!$issue_node) {
          $issue_node = $this->contributionStorage->saveIssue($issue_data, $user);
        }

        // Now, get the project for the issue.
        $project_data = $this->contributionRetriever->getDrupalOrgNode($issue_data->field_project->id, FALSE, REQUEST_TIME + (6 * 3600));
        if (!empty($project_data->title)) {
          $project_term = $this->contributionStorage->getProjectTerm($project_data->title);

          // We have everything we need. Save the issue comment as a code
          // contribution node.
          $this->contributionStorage->saveIssueComment($comment, $issue_node, $project_term, $user);
        }
      }
    }
  }

}
