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
  }

  /**
   * Check the type and number of files attached to a comment under a isssue.
   */
  public function getFileDetails($issueData, $comment) {

    // Get the files in the reverse order.
    $patchFiles = $totalFiles = 0;
    $matched = FALSE;
    foreach (array_reverse($issueData->field_issue_files) as $fileRecord) {
      $fileId = $fileRecord->file->id;
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
    $this->patchFiles = $patchFiles;
    $this->totalFiles = $totalFiles;
    $this->$fileId = $fileId;
    return $this;
  }

  /**
   * Determine the status of the Issue node.
   */
  public function determineIssueStatus($comment, $issueData) {
    $status = ($comment->created == $issueData->changed) ?
    $this->getStatusFromCode((int) $issueData->field_issue_status) :
    '';
    $this->status = $status;
    return $this;
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

}
