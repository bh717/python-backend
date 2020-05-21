<?php

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
  public function __construct(ContributionSourcePluginManager $pluginManager, ContributionTrackerStorage $contribStorage, LoggerChannelInterface $logger) {
    $this->pluginManager = $pluginManager;
    $this->contribStorage = $contribStorage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $container->get('plugin.manager.contribution_plugin_manager'),
      $container->get('plugin.manager.contribution_storage'),
      $container->get('logger.channel.ct_manager')
    );
  }

  /**
   * Collects user contribution and stores it.
   */
  public function processItem($data) {
    $plugin_instance = $this->pluginManager->createInstance($data->plugin_id);
    $userContribution = $plugin_instance->getUserInformation($data);
    $this->contribStorage->storeData($userContribution);
  }

}
