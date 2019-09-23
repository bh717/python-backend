<?php

namespace Drupal\contrib_tracker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeInterface;
use Drupal\slack\Slack;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\File as DrupalOrgFile;
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
      // @TODO: Breakup this code block. This could go in a different class.
      $nid = $comment->node->id;
      $link = sprintf("https://www.drupal.org/node/%s", $nid);

      // If we have stored this comment, we have stored everything after it.
      if ($this->contribStorage->getNodeForDrupalOrgIssueComment($comment->url)) {
        $this->logger->notice('Skipping @comment, and all after it.', ['@comment' => $comment->url]);
        break;
      }

      // This is a new comment. Get the issue node first.
      $this->logger->info('Retrieving issue @nid...', ['@nid' => $nid]);
      $issueData = $this->contribRetriever->getDrupalOrgNode($nid, FALSE, REQUEST_TIME + 180);
      if (isset($issueData->type) && $issueData->type == 'project_issue') {
        $issueNode = $this->contribStorage->getNodeForDrupalOrgIssue($link);
        if (!$issueNode) {
          $issueNode = $this->contribStorage->saveIssue($issueData, $user);
        }

        // Get the files in the reverse order.
        $patchFiles = $totalFiles = 0;
        $matched = FALSE;
        if (!empty($issueData->field_issue_files)) {
          $this->logger->info('Found @files files for the issue.', [
            '@files' => count($issueData->field_issue_files),
          ]);
          foreach (array_reverse($issueData->field_issue_files) as $fileRecord) {
            $fileId = $fileRecord->file->id;
            $this->logger->info('Getting file @fid...', ['@fid' => $fileId]);
            $fileData = $this->contribRetriever->getFile($fileId);
            if ($fileData->timestamp == $comment->created) {
              $totalFiles++;
              if ($this->isPatchFile($fileData)) {
                $patchFiles++;
              }

              // We have found the file.
              $matched = TRUE;
            }
            elseif ($matched) {
              // We have matched at least one file. If we don't have a match
              // anymore, stop looking for more.
              break;
            }
          }
        }
        $this->logger->info('Matched @total files, of which @patch are patches.', [
          '@total' => $totalFiles,
          '@patch' => $patchFiles,
        ]);

        // Try to determine the status.
        // Since we cannot access the revisions directly, we will see if the
        // issue was updated at the same time as this comment (by using the
        // 'changed' field). If it was, it is a safe assumption that the issue
        // status reflects the status set in the comment.
        // This is not accurate, especially for historic scans, but it is fairly
        // accurate for new issues and comments.
        $status = ($comment->created == $issueData->changed) ?
          $this->getStatusFromCode((int) $issueData->field_issue_status) :
          '';

        // Now, get the project for the issue.
        $this->logger->info('Getting project @nid...', ['@nid' => $issueData->field_project->id]);
        $projectData = $this->contribRetriever->getDrupalOrgNode($issueData->field_project->id, FALSE, REQUEST_TIME + (6 * 3600));
        if (!empty($projectData->title)) {
          $projectTerm = $this->contribStorage->getProjectTerm($projectData->title);

          // We have everything we need. Save the issue comment as a code
          // contribution node.
          $this->logger->notice('Saving issue comment @link...', ['@link' => $comment->url]);
          $this->contribStorage->saveIssueComment($comment, $issueNode, $projectTerm, $user, $patchFiles, $totalFiles, $status);

          $this->sendSlackNotification($user, $uid, $comment, $issueNode, $projectData, $patchFiles, $totalFiles, $status);
        }
      }
    }
  }

  /**
   * Sends Slack message to project group.
   */
  protected function sendSlackNotification(UserInterface $user, $uid, DrupalOrgComment $comment, NodeInterface $issueNode, DrupalOrgNode $project, $patchFiles, $totalFiles, $status) {
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

    if ($totalFiles > 0) {
      $msg .= sprintf(' with %d files (%d patch(es))', $totalFiles, $patchFiles);
    }

    if ($status) {
      $msg .= sprintf(' and changed the status to %s', $status);
    }

    $msg .= ".\n";
    $msg .= $commentBody;

    // And finally send the message.
    $this->slackService->sendMessage($msg);
  }

  /**
   * Determine if this is a patch file.
   *
   * @param \Hussainweb\DrupalApi\Entity\File $fileRecord
   *   The file data returned from API.
   *
   * @return bool
   *   TRUE if this is a patch file, else FALSE.
   */
  protected function isPatchFile(DrupalOrgFile $fileRecord) {
    return $fileRecord->mime == 'text/x-diff';
  }

  /**
   * Translate the status id to text.
   *
   * @param int $statusId
   *   Issue status id.
   *
   * @return string
   *   Readable text corresponding to the status id.
   */
  protected function getStatusFromCode($statusId) {
    $statusMap = [
      1 => 'active',
      2 => 'fixed',
      3 => 'closed',
      4 => 'postponed',
      5 => 'closed',
      6 => 'closed',
      // This is actually closed (fixed), but let's call it fixed.
      7 => 'fixed',
      8 => 'needs review',
      13 => 'needs work',
      14 => 'rtbc',
      15 => 'patch',
      16 => 'postponed',
      18 => 'closed',
    ];
    return (isset($statusMap[$statusId])) ? $statusMap[$statusId] : '';
  }

}
