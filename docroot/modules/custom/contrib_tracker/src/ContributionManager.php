<?php

namespace Drupal\contrib_tracker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Entity\File as DrupalOrgFile;

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
   * Logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ContributionManager constructor.
   *
   * @param \Drupal\contrib_tracker\ContributionStorageInterface $contribution_storage
   *   The injected contribution storage service.
   * @param \Drupal\contrib_tracker\ContributionRetrieverInterface $retriever
   *   The injected contribution retriever service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   */
  public function __construct(ContributionStorageInterface $contribution_storage, ContributionRetrieverInterface $retriever, LoggerChannelInterface $logger) {
    $this->contributionStorage = $contribution_storage;
    $this->contributionRetriever = $retriever;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function storeCommentsByDrupalOrgUser($uid, UserInterface $user) {
    /** @var \Hussainweb\DrupalApi\Entity\Comment $comment */
    foreach ($this->contributionRetriever->getDrupalOrgCommentsByAuthor($uid) as $comment) {
      // @TODO: Breakup this code block.
      $nid = $comment->node->id;
      $link = sprintf("https://www.drupal.org/node/%s", $nid);
      $comment_link = sprintf("https://www.drupal.org/node/%s#comment-%s", $nid, $comment->getId());

      // If we have stored this comment, we have stored everything after it.
      if ($this->contributionStorage->getNodeForDrupalOrgIssueComment($comment_link)) {
        $this->logger->notice('Skipping @comment, and all after it.', ['@comment' => $comment_link]);
        break;
      }

      // This is a new comment. Get the issue node first.
      $this->logger->info('Retrieving issue @nid...', ['@nid' => $nid]);
      $issue_data = $this->contributionRetriever->getDrupalOrgNode($nid, FALSE, REQUEST_TIME + 180);
      if (isset($issue_data->type) && $issue_data->type == 'project_issue') {
        $issue_node = $this->contributionStorage->getNodeForDrupalOrgIssue($link);
        if (!$issue_node) {
          $issue_node = $this->contributionStorage->saveIssue($issue_data, $user);
        }

        // Get the files in the reverse order.
        $patch_files = $total_files = 0;
        $matched = FALSE;
        if (!empty($issue_data->field_issue_files)) {
          $this->logger->info('Found @files for the issue.', [
            '@files' => count($issue_data->field_issue_files),
          ]);
          foreach (array_reverse($issue_data->field_issue_files) as $file_record) {
            $file_id = $file_record->file->id;
            $this->logger->info('Getting file @fid...', ['@fid' => $file_id]);
            $file_data = $this->contributionRetriever->getFile($file_id);
            if ($file_data->timestamp == $comment->created) {
              $total_files++;
              if ($this->isPatchFile($file_data)) {
                $patch_files++;
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
          '@total' => $total_files,
          '@patch' => $patch_files,
        ]);

        // Try to determine the status.
        // Since we cannot access the revisions directly, we will see if the
        // issue was updated at the same time as this comment (by using the
        // 'changed' field). If it was, it is a safe assumption that the issue
        // status reflects the status set in the comment.
        // This is not accurate, especially for historic scans, but it is fairly
        // accurate for new issues and comments.
        $status = ($comment->created == $issue_data->changed) ?
          $this->getStatusFromCode((int) $issue_data->field_issue_status) :
          '';

        // Now, get the project for the issue.
        $this->logger->info('Getting project @nid...', ['@nid' => $issue_data->project->id]);
        $project_data = $this->contributionRetriever->getDrupalOrgNode($issue_data->field_project->id, FALSE, REQUEST_TIME + (6 * 3600));
        if (!empty($project_data->title)) {
          $project_term = $this->contributionStorage->getProjectTerm($project_data->title);

          // We have everything we need. Save the issue comment as a code
          // contribution node.
          $this->logger->notice('Saving issue comment @link...', ['@link' => $comment_link]);
          $this->contributionStorage->saveIssueComment($comment, $issue_node, $project_term, $user, $patch_files, $total_files, $status);
        }
      }
    }
  }

  /**
   * Determine if this is a patch file.
   *
   * @param \Hussainweb\DrupalApi\Entity\File $file_record
   *   The file data returned from API.
   *
   * @return bool
   *   TRUE if this is a patch file, else FALSE.
   */
  protected function isPatchFile(DrupalOrgFile $file_record) {
    return $file_record->mime == 'text/x-diff';
  }

  /**
   * Translate the status id to text.
   *
   * @param int $status_id
   *   Issue status id.
   *
   * @return string
   *   Readable text corresponding to the status id.
   */
  protected function getStatusFromCode($status_id) {
    $status_map = [
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
    return (isset($status_map[$status_id])) ? $status_map[$status_id] : '';
  }

}
