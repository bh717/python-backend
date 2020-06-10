<?php

declare(strict_types=1);

namespace Drupal\ct_manager\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ct_manager\ContributionSourcePluginManager;
use Drupal\ct_manager\ContributionTrackerStorage;

/**
 * Processes users for individual plugin implementations.
 *
 * @QueueWorker(
 *   id = "ct_manager_process_users",
 *   title = @Translation("Process users for each plugin implementation of ct_manager"),
 *   cron = {"time" = 600}
 * )
 */
class ProcessUsers extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Contribution plugin manager.
   *
   * @var \Drupal\ct_manager\ContributionSourcePluginManager
   */
  protected $pluginManager;

  /**
   * Contribution manager service.
   *
   * @var \Drupal\ct_manager\ContributionTrackerStorage
   */
  protected $contribStorage;

  /**
   * The logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, ContributionSourcePluginManager $pluginManager, ContributionTrackerStorage $contribStorage, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->pluginManager = $pluginManager;
    $this->contribStorage = $contribStorage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('plugin.manager.contribution_plugin_manager'),
      $container->get('plugin.manager.contribution_storage'),
      $container->get('logger.channel.ct_manager')
    );
  }

  /**
   * Collects user contribution and stores it.
   */
  public function processItem($data) {
    /** @var \Drupal\ct_manager\ContributionProcessQueueItem $data */
    $plugin_id = $data->getPluginId();
    $user = $data->getUser();

    /** @var \Drupal\ct_manager\ContributionSourceInterface $plugin_instance */
    $plugin_instance = $this->pluginManager->createInstance($plugin_id);
    if (!($plugin_instance->isUserValid($user))) {
      $this->logger->error('@plugin username for @username is invalid.', ['@plugin' => $plugin_id, '@username' => $user->getUsername()]);
      return;
    }

    $issues = $plugin_instance->getUserIssues($user);
    $comments = $plugin_instance->getUserCodeContributions($user);

    $this->logger->notice('Saving @plugin issues for @user', ['@plugin' => $plugin_id, '@user' => $user->getUsername()]);
    foreach ($issues as $issue) {
      if ($this->contribStorage->getNodeForIssue($issue['url'])) {
        $this->logger->notice('Skipping issue @issue, and all after it.', ['@issue' => $issue['url']]);
        break;
      }
      $this->contribStorage->saveIssue($issue);
    }

    $this->logger->notice('Saving @plugin code contributions for @user', ['@plugin' => $plugin_id, '@user' => $user->getUsername()]);
    foreach ($comments as $comment) {
      if ($this->contribStorage->getNodeForCodeContribution($comment['url'])) {
        $this->logger->notice('Skipping code contribution @comment, and all after it.', ['@comment' => $comment['url']]);
        break;
      }
      $issueNode = $this->contribStorage->getOrCreateIssueNode($comment['issueUrl'], $comment['issueTitle']);
      $this->contribStorage->saveCodeContribution($comment, $issueNode, $user);
    }
  }

}
