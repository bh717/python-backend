<?php

namespace Drupal\contrib_tracker;

use Drupal\contrib_tracker\DrupalOrg\CommentDetails;
use Drupal\node\NodeInterface;
use Drupal\slack\Slack;
use Drupal\user\UserInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;

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
  protected $contribStorage;

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contribRetriever;

  /**
   * Slack service.
   *
   * @var \Drupal\slack\Slack
   */
  protected $slackService;

  /**
   * Logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ContributionManager constructor.
   *
   * @param \Drupal\contrib_tracker\ContributionStorageInterface $contributionStorage
   *   The injected contribution storage service.
   * @param \Drupal\contrib_tracker\ContributionRetrieverInterface $retriever
   *   The injected contribution retriever service.
   * @param \Drupal\slack\Slack $slackService
   *   Slack service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   */
  public function __construct(ContributionStorageInterface $contributionStorage, ContributionRetrieverInterface $retriever, Slack $slackService, LoggerChannelInterface $logger) {
    $this->contribStorage = $contributionStorage;
    $this->contribRetriever = $retriever;
    $this->slackService = $slackService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function storeCommentsByDrupalOrgUser($uid, UserInterface $user) {
    /** @var \Hussainweb\DrupalApi\Entity\Comment $comment */
    foreach ($this->contribRetriever->getDrupalOrgCommentsByAuthor($uid) as $comment) {
      $nid = $comment->node->id;
      // If we have stored this comment, we have stored everything after it.
      if ($this->contribStorage->getNodeForDrupalOrgIssueComment($comment->url)) {
        $this->logger->notice('Skipping @comment, and all after it.', ['@comment' => $comment->url]);
        break;
      }

      // This is a new comment. Get the issue node first.
      $this->logger->info('Retrieving issue @nid...', ['@nid' => $nid]);
      $issueData = $this->contribRetriever->getDrupalOrgNode($nid, REQUEST_TIME + 180);
      if (!isset($issueData->type) || $issueData->type != 'project_issue') {
        // This is not an issue. Skip it.
        continue;
      }

      $commentDetails = new CommentDetails($this->contribRetriever, $comment, $issueData);
      $issueNode = $this->getIssueNodeDetails($nid, $issueData, $user);

      if (!empty($issueData->field_issue_files)) {
        $this->logger->info('Found @files files for the issue.', [
          '@files' => count($issueData->field_issue_files),
        ]);
        $this->logger->info('Matched @total files, of which @patch are patches.', [
          '@total' => $commentDetails->getTotalFilesCount(),
          '@patch' => $commentDetails->getPatchFilesCount(),
        ]);
      }

      // Now, get the project for the issue.
      $this->logger->info('Getting project @nid...', ['@nid' => $issueData->field_project->id]);
      $projectData = $this->contribRetriever->getDrupalOrgNode($issueData->field_project->id, REQUEST_TIME + (6 * 3600));
      if (empty($projectData->title)) {
        // We couldn't get the project details for soem reason.
        // Skip the rest of the steps.
        $this->logger->warning('Failed to retrieve project data for @nid.', [
          '@nid' => $issueData->field_project->id,
        ]);
        continue;
      }

      $projectTerm = $this->contribStorage->getProjectTerm($projectData->title);

      // We have everything we need. Save the issue comment as a code
      // contribution node.
      $this->logger->notice('Saving issue comment @link...', ['@link' => $comment->url]);
      $this->contribStorage->saveIssueComment($comment, $commentDetails, $issueNode, $projectTerm, $user);
      $this->sendSlackNotification($comment, $commentDetails, $issueNode, $projectData, $user, $uid);
    }
  }

  /**
   * Retrives the node id and data for every comment on an issue.
   */
  public function getIssueNodeDetails($nid, $issueData, $user) {
    $link = sprintf("https://www.drupal.org/node/%s", $nid);
    $issueNode = $this->contribStorage->getNodeForDrupalOrgIssue($link);
    if (!$issueNode) {
      $issueNode = $this->contribStorage->saveIssue($issueData, $user);
    }
    return $issueNode;
  }

  /**
   * Sends Slack message to project group.
   */
  public function sendSlackNotification(DrupalOrgComment $comment, CommentDetails $commentDetails, NodeInterface $issueNode, DrupalOrgNode $project, UserInterface $user, $uid) {
    // @TODO: Refactor this whole method to take lesser parameters.
    // Only send a notification if the comment was posted in the last hour.
    if ($comment->created < time() - 3600) {
      return;
    }

    $commentBody = '';
    if (!empty($comment->comment_body->value)) {
      $commentBody = strip_tags($comment->comment_body->value);
      $commentBody = (strlen($commentBody) > 80) ? (substr($commentBody, 0, 77) . '...') : '';
    }

    // First generate the message.
    $msg = sprintf('<a href="https://www.drupal.org/user/%s">%s</a>', $uid, $user->getDisplayName());
    $msg .= sprintf(' posted a comment on <a href="%s">%s</a>', $comment->url, $issueNode->getTitle());
    $msg .= sprintf(' in project <a href="%s">%s</a>', $project->url, $project->title);

    if ($commentDetails->getTotalFilesCount() > 0) {
      $msg .= sprintf(' with %d files (%d patch(es))', $commentDetails->getTotalFilesCount(), $commentDetails->getPatchFilesCount());
    }

    if ($commentDetails->getIssueStatus()) {
      $msg .= sprintf(' and changed the status to %s', $commentDetails->getIssueStatus());
    }

    $msg .= ".\n";
    $msg .= $commentBody;

    // And finally send the message.
    $this->slackService->sendMessage($msg);
  }

}
