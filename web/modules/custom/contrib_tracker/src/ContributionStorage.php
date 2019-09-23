<?php

namespace Drupal\contrib_tracker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Entity\Comment as DrupalOrgComment;
use Hussainweb\DrupalApi\Entity\Node as DrupalOrgNode;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Contribution storage service class.
 *
 * This class is responsible for storing contribution information in various
 * nodes and terms. It provides methods to store the information in specific
 * content types and vocabularies corresponding to the type of the contribution
 * being stored.
 */
class ContributionStorage implements ContributionStorageInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Node storage controller.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * ContributionStorage constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The injected entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function saveIssueComment(DrupalOrgComment $comment, NodeInterface $issueNode, TermInterface $projectTerm, UserInterface $user, $patchFiles, $totalFiles, $status) {
    $commentBody = '';
    $commentTitle = 'Comment on ' . $issueNode->getTitle();

    if (!empty($comment->comment_body->value)) {
      $commentBody = $comment->comment_body->value;
      $commentTitle = strip_tags($commentBody);
      if (strlen($commentTitle) > 80) {
        $commentTitle = substr($commentTitle, 0, 77) . '...';
      }
    }

    $node = $this->nodeStorage->create([
      'type' => 'code_contribution',
      'title' => $commentTitle,
      'field_code_contrib_link' => $comment->url,
      'field_contribution_author' => $user->id(),
      'field_contribution_date' => date('Y-m-d', $comment->created),
      'field_contribution_description' => [
        'value' => $commentBody,
        'format' => 'basic_html',
      ],
      'field_code_contrib_issue_link' => $issueNode->id(),
      'field_code_contrib_project' => $projectTerm->id(),
      'field_code_contrib_issue_status' => $status,
      'field_contribution_technology' => $this->getDrupalTechnologyId(),
      'field_code_contrib_files_count' => $totalFiles,
      'field_code_contrib_patches_count' => $patchFiles,
    ]);
    $node->save();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function saveIssue(DrupalOrgNode $issueData, UserInterface $user) {
    $title = isset($issueData->title) ? $issueData->title : '(not found)';

    // Create an issue.
    $node = $this->nodeStorage->create([
      'type' => 'issue',
      'title' => $title,
      'field_issue_link' => sprintf("https://www.drupal.org/node/%s", $issueData->getId()),
    ]);
    $node->save();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeForDrupalOrgIssue($issueLink) {
    $issues = $this->nodeStorage->getQuery()
      ->condition('type', 'issue')
      ->condition('field_issue_link', $issueLink)
      ->execute();

    return (count($issues) > 0) ? $this->nodeStorage->load(reset($issues)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeForDrupalOrgIssueComment($commentLink) {
    $nodes = $this->nodeStorage->getQuery()
      ->condition('type', 'code_contribution')
      ->condition('field_code_contrib_link', $commentLink)
      ->execute();

    return (count($nodes) > 0) ? $this->nodeStorage->load(reset($nodes)) : NULL;
  }

  /**
   * Get the id of the Drupal term in technology vocabulary.
   *
   * @return int
   *   The term id for Drupal term.
   */
  protected function getDrupalTechnologyId() {
    return $this->getOrCreateTerm('Drupal', 'technology')->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectTerm($projectName) {
    return $this->getOrCreateTerm($projectName, 'project');
  }

  /**
   * Get (or create) a term in a specified vocabulary.
   *
   * @param string $termName
   *   Name of the term to be retrieved or created.
   * @param string $vocabulary
   *   Machine name of the vocabulary.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The term with the given name in the given vocabulary.
   */
  protected function getOrCreateTerm($termName, $vocabulary) {
    $terms = $this->termStorage->getQuery()
      ->condition('name', $termName)
      ->condition('vid', $vocabulary)
      ->execute();

    if (count($terms) == 0) {
      $term = $this->termStorage->create([
        'name' => $termName,
        'vid' => $vocabulary,
      ]);
      $term->save();
      return $term;
    }

    return $this->termStorage->load(reset($terms));
  }

}
