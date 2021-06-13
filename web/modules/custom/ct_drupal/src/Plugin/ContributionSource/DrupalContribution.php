<?php

declare(strict_types=1);

namespace Drupal\ct_drupal\Plugin\ContributionSource;

use DateTimeImmutable;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ct_drupal\CommentDetails;
use Drupal\ct_drupal\DrupalRetrieverInterface;
use Drupal\ct_manager\ContributionSourceInterface;
use Drupal\ct_manager\ContributionTrackerStorage;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;
use Drupal\do_username\DOUserInfoRetriever;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve and Store contributions from drupal.org.
 *
 * @ContributionSource(
 *   id = "drupal",
 *   description = @Translation("Retrieve and store contribution data from drupal.org."),
 * )
 */
class DrupalContribution extends PluginBase implements ContributionSourceInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Retrievers for each user.
   *
   * @var \Drupal\ct_drupal\DrupalOrgRetriever
   */
  protected $doRetriever;

  /**
   * do_username service.
   *
   * @var \Drupal\do_username\DOUserInfoRetriever
   */
  protected $doUserInfoRetriever;

  /**
   * Contribution Storage service.
   *
   * @var \Drupal\ct_manager\ContributionTrackerStorage
   */
  protected $contributionStorage;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin_definition for the plugin instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The injected entity type manager service.
   * @param \Drupal\ct_drupal\DrupalOrgRetriever $doRetriever
   *   Wrapper for Drupal.org API.
   * @param \Drupal\do_username\DOUserInfoRetriever $doUserInfoRetriever
   *   The injected DO UserInfoRetriever service.
   * @param \Drupal\ct_manager\ContributionTrackerStorage $contributionStorage
   *   The contribution storage service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   The logger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, DrupalRetrieverInterface $doRetriever, DOUserInfoRetriever $doUserInfoRetriever, ContributionTrackerStorage $contributionStorage, LoggerChannelInterface $loggerChannel) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->doRetriever = $doRetriever;
    $this->doUserInfoRetriever = $doUserInfoRetriever;
    $this->contributionStorage = $contributionStorage;
    $this->logger = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('ct_drupal.retriever'),
      $container->get('do_username.user_service'),
      $container->get('ct_manager.contribution_storage'),
      $container->get('logger.channel.ct_drupal')
    );
  }

  /**
   * Get all users with drupal.org username.
   */
  public function getUsers() {
    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('field_do_username', '', '!=')
      ->condition('status', 1)
      ->execute();
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function isUserValid(User $user): bool {
    $do_username = $user->field_do_username[0]->getValue()['value'];
    $userInformation = $this->doUserInfoRetriever->getUserInformation($do_username);
    return isset($userInformation->uid);
  }

  /**
   * Get issues from the total contribution data.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function getUserIssues(User $user) {
    return [];
  }

  /**
   * Get comments from the total contribution data.
   */
  public function getUserCodeContributions(User $user) {
    $do_username = $user->field_do_username[0]->getValue()['value'];
    $userInformation = $this->doUserInfoRetriever->getUserInformation($do_username);

    $retriever = $this->doRetriever;

    $codeContribution = [];
    foreach ($retriever->getCommentsByAuthor($userInformation->uid) as $comment) {
      // If we have stored this comment, we have stored everything after it.
      if ($this->contributionStorage->getNodeForCodeContribution($comment->url)) {
        $this->logger->notice('Skipping @comment, and all after it.', ['@comment' => $comment->url]);
        break;
      }

      $issueNode = $retriever->getDrupalOrgNode($comment->node->id, REQUEST_TIME + 1800);
      $issueData = $issueNode->getData();
      if (!isset($issueData->type) || $issueData->type != 'project_issue') {
        // This is not an issue. Skip it.
        continue;
      }
      $commentDetails = new CommentDetails($retriever, $comment);

      // Now, get the project for the issue.
      $projectData = $retriever->getDrupalOrgNode($issueData->field_project->id, REQUEST_TIME + (6 * 3600))->getData();
      if (empty($projectData->title)) {
        // We couldn't get the project details for some reason.
        // Skip the rest of the steps.
        continue;
      }

      $commentBody = $commentDetails->getDescription();
      $title = 'Comment on ' . $issueData->title;
      if (!empty($commentBody)) {
        $title = strip_tags($commentBody);
      }
      if (strlen($title) > 80) {
        $title = substr($title, 0, 77) . '...';
      }

      $issue_url = sprintf("https://www.drupal.org/node/%s", $issueNode->getId());
      $date = (new DateTimeImmutable())->setTimestamp((int) $issueData->created);
      $commit = (new CodeContribution($title, $comment->url, $date))
        ->setAccountUrl('https://www.drupal.org/user/' . $comment->author->id)
        ->setDescription($commentBody)
        ->setProject($projectData->title)
        ->setTechnology('Drupal')
        ->setProjectUrl($projectData->url)
        ->setIssue(new Issue($issueData->title, $issue_url))
        ->setPatchCount($commentDetails->getPatchFilesCount())
        ->setFilesCount($commentDetails->getTotalFilesCount())
        ->setStatus($commentDetails->getIssueStatus());
      $codeContribution[] = $commit;
    }
    // Contribution Storage in ct_manager expects this array to be sorted
    // in descending order.
    $codeContribution = array_values($codeContribution);
    usort(
      $codeContribution,
      // phpcs:ignore
      fn(CodeContribution $first, CodeContribution $second) => $second->getDate()->getTimestamp() <=> $first->getDate()->getTimestamp()
    );
    return $codeContribution;
  }

  /**
   * Get message for notification.
   */
  public function getNotificationMessage(CodeContribution $contribution, User $user): string {
    $commentBody = $contribution->getDescription() ?: '';
    $commentBody = (strlen($commentBody) > 80) ? (substr($commentBody, 0, 77) . '...') : '';
    $issue = $contribution->getIssue();
    $msg = sprintf('<a href="%s">%s</a>', $contribution->getAccountUrl(), $user->getDisplayName());
    $msg .= sprintf(' posted a comment on <a href="%s">%s</a>', $contribution->getUrl(), $issue->getTitle());
    $msg .= sprintf(' in project <a href="%s">%s</a>.', $contribution->getProjectUrl(), $contribution->getProject());
    $msg .= "\n";
    // Get patch file count.
    if ($contribution->getFilesCount() > 0) {
      $msg .= sprintf(' with %d files (%d patch(es))', $contribution->getFilesCount(), $contribution->getPatchCount());
    }
    // Get issue status.
    if ($contribution->getStatus()) {
      $msg .= sprintf(' and changed the status to %s', $contribution->getStatus());
    }

    $msg .= $commentBody;

    return $msg;
  }

}
