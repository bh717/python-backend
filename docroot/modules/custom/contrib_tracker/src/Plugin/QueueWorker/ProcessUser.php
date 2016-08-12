<?php

namespace Drupal\contrib_tracker\Plugin\QueueWorker;

use Drupal\contrib_tracker\UserResolverInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
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

  protected $userResolver;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (is_string($data)) {
      $user = $this->userResolver->getUserInformation($data);
      $uid = $user->getId();
    }
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserResolverInterface $user_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userResolver = $user_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('contrib_tracker.user_resolver')
    );
  }
}
