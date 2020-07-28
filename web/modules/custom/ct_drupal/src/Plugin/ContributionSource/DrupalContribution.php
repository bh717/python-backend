<?php

declare(strict_types=1);

namespace Drupal\ct_drupal\Plugin\ContributionSource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ct_manager\ContributionSourceInterface;
use Drupal\user\Entity\User;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\CodeContributionCollection;
use Drupal\ct_manager\Data\IssueCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\do_username\DOUserInfoRetriever;

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
   * @param \Drupal\do_username\DOUserInfoRetriever $doUserInfoRetriever
   *   The injected DO UserInfoRetriever service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, DOUserInfoRetriever $doUserInfoRetriever) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('do_username.user_service')
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
   */
  public function getUserIssues(User $user): IssueCollection {
    return new IssueCollection([]);
  }

  /**
   * Get comments from the total contribution data.
   */
  public function getUserCodeContributions(User $user): CodeContributionCollection {
    return new CodeContributionCollection([]);
  }

  /**
   * Get message for notification.
   */
  public function getNotificationMessage(CodeContribution $contribution, User $user): string {
    return '';
  }

}
