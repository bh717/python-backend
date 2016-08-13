<?php

namespace Drupal\contrib_tracker\Plugin\QueueWorker;

use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Drupal\contrib_tracker\ContributionStorageInterface;
use Drupal\contrib_tracker\DrupalOrg\Client;
use Drupal\contrib_tracker\UserResolverInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\UserInterface;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve user's information from drupal.org
 *
 * @QueueWorker(
 *   id = "contrib_tracker_process_users",
 *   title = @Translation("Process users for contribution tracking"),
 *   cron = {"time" = 30}
 * )
 */
class ProcessUser extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contributionRetriever;

  /**
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
   */
  protected $contributionStorage;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (is_a($data, UserInterface::class)) {
      $do_username = $data->field_drupalorg_username[0]->getValue()['value'];
      $do_user = $this->contributionRetriever->getUserInformation($do_username);
      $uid = $do_user->getId();

      // @TODO: Refactor into another service.
      /** @var \Hussainweb\DrupalApi\Entity\Comment $comment */
      foreach ($this->contributionRetriever->getDrupalOrgCommentsByAuthor($uid) as $comment) {
        $nid = $comment->node->id;
        $link = sprintf("https://www.drupal.org/node/%s", $nid);
        $comment_link = sprintf("https://www.drupal.org/node/%s#comment-%s", $nid, $comment->getId());

        // If we have stored this comment, we have stored everything after it as well.
        if ($this->contributionStorage->getNodeForDrupalOrgIssueComment($comment_link)) {
          break;
        }

        // This is a new comment. Get the issue node first.
        $issue_data = $this->contributionRetriever->getDrupalOrgNode($nid, FALSE, REQUEST_TIME + 600);
        if (isset($issue_data->type) && $issue_data->type == 'project_issue') {
          $issue_node = $this->contributionStorage->getNodeForDrupalOrgIssue($link);
          if (!$issue_node) {
            $issue_node = $this->contributionStorage->saveIssue($issue_data, $data);
          }

          // Now, get the project for the issue.
          $project_data = $this->contributionRetriever->getDrupalOrgNode($issue_data->field_project->id, FALSE, REQUEST_TIME + (6 * 3600));
          if (!empty($project_data->title)) {
            $project_term = $this->contributionStorage->getProjectTerm($project_data->title);

            // We have everything we need. Save the issue comment as a code
            // contribution node.
            $this->contributionStorage->saveIssueComment($comment, $issue_node, $project_term, $data);
          }
        }
      }
    }
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContributionRetrieverInterface $retriever, ContributionStorageInterface $contribution_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contributionRetriever = $retriever;
    $this->contributionStorage = $contribution_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('contrib_tracker_retriever'),
      $container->get('contrib_tracker_storage')
    );
  }
}
