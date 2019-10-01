<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Hussainweb\DrupalApi\Entity\File as DrupalOrgFile;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;

/**
 * Instance of Drupal.org Comment.
 *
 * This class holds the instance of the Drupal org comment from
 * every Issue node.
 */
class CommentDetails {

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contribRetriever;

  /**
   * Comment details from d.o.
   *
   * @var \Hussainweb\DrupalApi\Entity\Comment
   */
  protected $comment;

  /**
   * Node details from d.o.
   *
   * @var \Hussainweb\DrupalApi\Entity\Node
   */
  protected $issueData;

  /**
   * Number of patches attached to the comment.
   *
   * @var int
   */
  protected $patchFilesCount;

  /**
   * Number of all files attached to the comment.
   *
   * @var int
   */
  protected $totalFilesCount;

  /**
   * Issue status.
   *
   * @var string
   */
  protected $issueStatus;

  /**
   * DrupalOrgCommentDetails constructor.
   *
   * @param \Drupal\contrib_tracker\ContributionRetrieverInterface $retriever
   *   The injected contribution retriever service.
   * @param \Hussainweb\DrupalApi\Entity\Comment $comment
   *   The comment data from drupal.org.
   * @param \Hussainweb\DrupalApi\Entity\Node $issueData
   *   The issue data.
   */
  public function __construct(ContributionRetrieverInterface $retriever, DrupalOrgComment $comment, DrupalOrgNode $issueData) {
    $this->contribRetriever = $retriever;
    $this->comment = $comment;
    $this->issueData = $issueData;

    $this->processFileDetails();
    $this->determineIssueStatus();
  }

  /**
   * Get the number of patch files in this comment.
   */
  public function getPatchFilesCount(): int {
    return $this->patchFilesCount;
  }

  /**
   * Get the number of all files in this comment.
   */
  public function getTotalFilesCount(): int {
    return $this->totalFilesCount;
  }

  /**
   * Get the issue status.
   */
  public function getIssueStatus(): string {
    return $this->issueStatus;
  }

  /**
   * Check the type and number of files attached to a comment under a issue.
   */
  protected function processFileDetails(): void {
    // Get the files in the reverse order.
    $this->patchFilesCount = $this->totalFilesCount = 0;
    $matched = FALSE;
    foreach (array_reverse($this->issueData->field_issue_files) as $fileRecord) {
      $fileId = $fileRecord->file->id;
      $fileData = $this->contribRetriever->getFile($fileId);
      if ($fileData->timestamp == $this->comment->created) {
        $this->totalFilesCount++;
        if ($this->isPatchFile($fileData)) {
          $this->patchFilesCount++;
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

  /**
   * Determine the status of the issue.
   */
  protected function determineIssueStatus(): void {
    // Try to determine the status.
    // Since we cannot access the revisions directly, we will see if the
    // issue was updated at the same time as this comment (by using the
    // 'changed' field). If it was, it is a safe assumption that the issue
    // status reflects the status set in the comment.
    // This is not accurate, especially for historic scans, but it is fairly
    // accurate for new issues and comments.
    $status = ($this->comment->created == $this->issueData->changed) ?
      $this->getStatusFromCode((int) $this->issueData->field_issue_status) :
      '';
    $this->status = $status;
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
  protected function getStatusFromCode(int $statusId): string {
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

  /**
   * Determine if this is a patch file.
   *
   * @param \Hussainweb\DrupalApi\Entity\File $fileRecord
   *   The file data returned from API.
   *
   * @return bool
   *   TRUE if this is a patch file, else FALSE.
   */
  protected function isPatchFile(DrupalOrgFile $fileRecord): bool {
    return $fileRecord->mime == 'text/x-diff';
  }

}
