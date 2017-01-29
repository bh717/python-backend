<?php

namespace Drupal\contrib_tracker\Plugin\QueueWorker;

use Drupal\contrib_tracker\ContributionManagerInterface;
use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Drupal\contrib_tracker\ContributionStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve user's information from drupal.org.
 *
 * @QueueWorker(
 *   id = "contrib_tracker_process_users",
 *   title = @Translation("Process users for contribution tracking"),
 *   cron = {"time" = 600}
 * )
 */
class ProcessUser extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Contribution manager service.
   *
   * @var \Drupal\contrib_tracker\ContributionManagerInterface
   */
  protected $contributionManager;

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contributionRetriever;

  /**
   * Contribution storage service.
   *
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
   */
  protected $contributionStorage;

  /**
   * The logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (is_a($data, UserInterface::class)) {
      $do_username = $data->field_drupalorg_username[0]->getValue()['value'];
      if (!$do_username) {
        // We shouldn't really reach here, but if we do, leave quietly.
        return;
      }

      try {
        $do_user = $this->contributionRetriever->getUserInformation($do_username);
      }
      catch (\RuntimeException $ex) {
        // @TODO: Use a better exception class, and then rearrange catch blocks.
        $this->logger->error('User with d.o username "@username" not found', ['@username' => $do_username]);
        return;
      }

      $uid = $do_user->getId();

      $this->logger->notice('Processing user with d.o uid @username (@uid)...', [
        '@username' => $do_username,
        '@uid' => $uid,
      ]);

      // Store all comments by the user.
      $this->contributionManager->storeCommentsByDrupalOrgUser($uid, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContributionManagerInterface $manager, ContributionRetrieverInterface $retriever, ContributionStorageInterface $contribution_storage, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contributionManager = $manager;
    $this->contributionRetriever = $retriever;
    $this->contributionStorage = $contribution_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('contrib_tracker_manager'),
      $container->get('contrib_tracker_retriever'),
      $container->get('contrib_tracker_storage'),
      $container->get('logger.channel.contrib_tracker')
    );
  }

}
