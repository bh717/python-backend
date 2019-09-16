<?php

namespace Drupal\contrib_tracker\Command;

// @codingStandardsIgnoreLine
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\contrib_tracker\ContributionStorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class IssuesSanitiseCommand.
 *
 * @DrupalCommand (
 *     extension="contrib_tracker",
 *     extensionType="module"
 * )
 */
class IssuesSanitiseCommand extends Command {

  /**
   * Drupal\contrib_tracker\ContributionStorageInterface definition.
   *
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
   */
  protected $contribTrackStorage;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * IssuesSanitiseCommand constructor.
   *
   * @param \Drupal\contrib_tracker\ContributionStorageInterface $contribTrackStorage
   *   The contrib tracker storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ContributionStorageInterface $contribTrackStorage, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->$contribTrackStorage = $contribTrackStorage;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('contrib_tracker:issues:sanitise')
      ->setDescription($this->trans('commands.contrib_tracker.issues.sanitise.description'))
      ->addOption('chunk', NULL, InputOption::VALUE_REQUIRED, 'Number of nodes to delete in one chunk.', 100);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('Beginning to sanitize all duplicate issues.');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $chunk_size = $input->getOption('chunk');

    // Identify the duplicate nodes.
    // First, get the list of issue IDs that seem to have a duplicate.
    $duplicate_issue_ids = $this->getDuplicateIssueDoIds();
    $this->getIo()->info(sprintf("Found %d issues duplicated.", count($duplicate_issue_ids)));

    // Pick the oldest need as the "keeper" and prepare to delete the rest.
    array_map(function ($do_issue_id) use ($node_storage, $chunk_size) {
      $this->getIo()->info(sprintf("Getting all nodes for issue ID %s", $do_issue_id));
      $nids = $this->getNidsForDoIssue($do_issue_id);

      // The function above returns nids sorted by created date. We want to keep
      // the oldest one and use it to fix any references.
      $original_nid = array_shift($nids);
      $this->getIo()->info(sprintf("Found %d nodes and preserving nid %d as our node of choice.", count($nids) + 1, $original_nid));

      // Process only a few nodes at a time.
      $chunks = array_chunk($nids, $chunk_size);
      array_map(function ($chunk) use ($original_nid, $node_storage) {
        $this->getIo()->info(sprintf("Updating references and deleting %d nodes in this chunk.", count($chunk)));
        $this->updateReferencesForIssueNid($chunk, $original_nid);
        $nodes = $node_storage->loadMultiple($chunk);
        $node_storage->delete($nodes);
        unset($nodes);
      }, $chunks);

      // Fix the link format in the node we are keeping.
      $issue_node = $node_storage->load($original_nid);
      $issue_link = sprintf("https://www.drupal.org/node/%s", $do_issue_id);
      if ($issue_node->field_issue_link != $issue_link) {
        $this->getIo()->info("Updating the issue link in the original node we kept.");
        $issue_node->field_issue_link = $issue_link;
        $issue_node->save();
      }
      unset($issue_node);
    }, $duplicate_issue_ids);

    $this->getIo()->info($this->trans('commands.contrib_tracker.issues.sanitise.messages.success'));
  }

  /**
   * Get a list of IDs which are duplicated.
   *
   * @return string[]
   *   Array of IDs which are duplicated.
   */
  protected function getDuplicateIssueDoIds() {
    // SELECT entity_id, SUBSTRING_INDEX(field_issue_link_uri, "/", -1) AS nid,
    // COUNT(*) as c FROM node__field_issue_link GROUP BY nid HAVING c > 1
    // ORDER BY c DESC;.
    $results = $this->database->query("SELECT SUBSTRING_INDEX(field_issue_link_uri, '/', -1) AS doissueid, COUNT(entity_id) as c FROM {node__field_issue_link} GROUP BY doissueid HAVING COUNT(entity_id) > 1 ORDER BY c DESC;");
    return $results->fetchCol(0);
  }

  /**
   * Get node ids linked to a d.o issue.
   *
   * @param string $issueId
   *   The d.o issue ID.
   *
   * @return int[]
   *   Array of node ids.
   */
  protected function getNidsForDoIssue($issueId) {
    // SELECT DISTINCT n.nid FROM node__field_issue_link il
    // INNER JOIN node_field_data n ON il.entity_id = n.nid
    // WHERE SUBSTRING_INDEX(field_issue_link_uri, "/", -1) = '$issueId'
    // ORDER BY created ASC;.
    $sql = "SELECT DISTINCT n.nid FROM {node__field_issue_link} il
            INNER JOIN {node_field_data} n ON il.entity_id = n.nid
            WHERE SUBSTRING_INDEX(field_issue_link_uri, '/', -1) = :doissueid
            ORDER BY created ASC;";
    return $this->database->query($sql, [
      ':doissueid' => $issueId,
    ])->fetchCol(0);
  }

  /**
   * Replace references to various node ids with a different node id.
   *
   * @param int[] $issueNids
   *   The node ids to be searched and replaced.
   * @param int[] $originalNid
   *   The updated node id value.
   *
   * @return int
   *   Number of records updated.
   */
  protected function updateReferencesForIssueNid(array $issueNids, array $originalNid) {
    return $this->database->update('node__field_code_contrib_issue_link')
      ->fields([
        'field_code_contrib_issue_link_target_id' => $originalNid,
      ])
      ->condition('field_code_contrib_issue_link_target_id', $issueNids, 'IN')
      ->execute();
  }

}
