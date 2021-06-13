<?php

declare(strict_types=1);

namespace Drupal\ct_drupal\Plugin\ContributionSource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ct_drupal\Client;
use Drupal\ct_drupal\DrupalOrgRetriever;
use Drupal\ct_manager\ContributionSourceInterface;
use Drupal\ct_manager\Data\CodeContribution;
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
   * Drupal.org client service.
   *
   * @var \Drupal\ct_drupal\Client
   */
  protected $client;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Retrievers for each user.
   *
   * @var \Drupal\ct_drupal\DrupalOrgRetriever[]
   */
  protected $retrievers;

  /**
   * do_username service.
   *
   * @var \Drupal\do_username\DOUserInfoRetriever
   */
  protected $doUserInfoRetriever;

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
   * @param \Drupal\ct_drupal\Client $client
   *   The injected drupal.org client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The injected cache backend service.
   * @param \Drupal\do_username\DOUserInfoRetriever $doUserInfoRetriever
   *   The injected DO UserInfoRetriever service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Client $client, CacheBackendInterface $cacheBackend, DOUserInfoRetriever $doUserInfoRetriever) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->client = $client;
    $this->cache = $cacheBackend;
    $this->doUserInfoRetriever = $doUserInfoRetriever;
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
      $container->get('ct_drupal.client'),
      $container->get('cache.data'),
      $container->get('do_username.user_service'),
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
   * Returns a user retriever object.
   */
  protected function getOrCreateRetriever(User $user): DrupalOrgRetriever {
    $username = $user->field_do_username[0]->getValue()['value'];
    if (isset($this->retrievers[$username])) {
      return $this->retrievers[$username];
    }
    $this->retrievers[$username] = new DrupalOrgRetriever($this->client, $username, $this->cache);
    return $this->retrievers[$username];
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
   */
  public function getUserIssues(User $user) {
    return [];
  }

  /**
   * Get comments from the total contribution data.
   */
  public function getUserCodeContributions(User $user) {
    return $this->getOrCreateRetriever($user)->getCodeContribution();
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
