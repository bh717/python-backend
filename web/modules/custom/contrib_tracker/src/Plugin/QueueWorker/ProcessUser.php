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
  protected $contribManager;

  /**
   * Contribution retriever service.
   *
   * @var \Drupal\contrib_tracker\ContributionRetrieverInterface
   */
  protected $contribRetriever;

  /**
   * Contribution storage service.
   *
   * @var \Drupal\contrib_tracker\ContributionStorageInterface
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
  public function processItem($data) {
    if (is_a($data, UserInterface::class)) {
      $doUsername = $data->field_drupalorg_username[0]->getValue()['value'];
      if (!$doUsername) {
        // We shouldn't really reach here, but if we do, leave quietly.
        return;
      }

      try {
        $doUser = $this->contribRetriever->getUserInformation($doUsername);
      }
      catch (\RuntimeException $ex) {
        // @TODO: Use a better exception class, and then rearrange catch blocks.
        $this->logger->error('User with d.o username "@username" not found', ['@username' => $doUsername]);
        return;
      }

      $uid = $doUser->getId();

      $this->logger->notice('Processing user with d.o uid @username (@uid)...', [
        '@username' => $doUsername,
        '@uid' => $uid,
      ]);

      // Store all comments by the user.
      $this->contribManager->storeCommentsByDrupalOrgUser($uid, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, ContributionManagerInterface $manager, ContributionRetrieverInterface $retriever, ContributionStorageInterface $contributionStorage, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->contribManager = $manager;
    $this->contribRetriever = $retriever;
    $this->contribStorage = $contributionStorage;
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
      $container->get('contrib_tracker_manager'),
      $container->get('contrib_tracker_retriever'),
      $container->get('contrib_tracker_storage'),
      $container->get('logger.channel.contrib_tracker')
    );
  }

}
