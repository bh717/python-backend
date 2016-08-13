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

class ContributionStorage implements ContributionStorageInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contributionRetriever;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContributionRetrieverInterface $retriever) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    // @TODO: Consider removing contrib retriever from this class.
    $this->contributionRetriever = $retriever;
  }

  /**
   * {@inheritdoc}
   */
  public function saveIssueComment(DrupalOrgComment $comment, NodeInterface $issue_node, TermInterface $project_term, UserInterface $user) {
    if (!empty($comment->comment_body->value)) {
      $comment_body = $comment->comment_body->value;
      $comment_title = strip_tags($comment_body);
      if (strlen($comment_title) > 80) {
        $comment_title = substr($comment_title, 0, 77) . '...';
      }
    }
    else {
      $comment_body = '';
      $comment_title = 'Comment on ' . $issue_node->getTitle();
    }

    $node = $this->nodeStorage->create([
      'type' => 'code_contribution',
      'title' => $comment_title,
      'field_code_contrib_link' => $comment->url,
      'field_contribution_author' => $user->id(),
      'field_contribution_date' => date('Y-m-d', $comment->created),
      'field_contribution_description' => $comment_body,
      'field_code_contrib_issue_link' => $issue_node->id(),
      'field_code_contrib_project' => $project_term->id(),
//      'field_code_contrib_issue_status' => '',
      'field_contribution_technology' => $this->getDrupalTechnologyId(),
    ]);
    $node->save();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function saveIssue(DrupalOrgNode $issue_data, UserInterface $user) {
    $title = isset($issue_data->title) ? $issue_data->title : '(not found)';

    // Create an issue.
    $node = $this->nodeStorage->create([
      'type' => 'issue',
      'title' => $title,
      'field_issue_link' => $issue_data->url,
    ]);
    $node->save();
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeForDrupalOrgIssue($issue_link) {
    $issues = $this->nodeStorage->getQuery()
      ->condition('type', 'issue')
      ->condition('field_issue_link', $issue_link)
      ->execute();

    return (count($issues) > 0) ? $this->nodeStorage->load(reset($issues)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeForDrupalOrgIssueComment($comment_link) {
    $nodes = $this->nodeStorage->getQuery()
      ->condition('type', 'code_contribution')
      ->condition('field_code_contrib_link', $comment_link)
      ->execute();

    return (count($nodes) > 0) ? $this->nodeStorage->load(reset($nodes)) : NULL;
  }

  protected function getDrupalTechnologyId() {
    return $this->getOrCreateTerm('Drupal', 'technology')->id();
  }

  public function getProjectTerm($project_name) {
    return $this->getOrCreateTerm($project_name, 'project');
  }

  protected function getOrCreateTerm($term_name, $vocabulary) {
    $terms = $this->termStorage->getQuery()
      ->condition('name', $term_name)
      ->condition('vid', $vocabulary)
      ->execute();

    if (count($terms) == 0) {
      $term = $this->termStorage->create([
        'name' => $term_name,
        'vid' => $vocabulary,
      ]);
      $term->save();
      return $term;
    }

    return $this->termStorage->load(reset($terms));
  }

}
