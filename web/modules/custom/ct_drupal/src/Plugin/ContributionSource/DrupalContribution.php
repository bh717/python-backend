<?php

declare(strict_types=1);

namespace Drupal\ct_drupal\Plugin\ContributionSource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ct_manager\ContributionSourceInterface;
use Drupal\user\Entity\User;
use Drupal\ct_manager\Data\CodeContribution;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\do_username\DOUserInfoRetriever;
use Drupal\ct_drupal\DrupalRetrieverInterface;

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
   * Contribution retriever service.
   *
   * @var \Drupal\ct_drupal\DrupalRetrieverInterface
   */
  protected $contribRetriever;

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
   * @param \Drupal\do_username\DrupalRetrieverInterface $retriever
   *   The injected DrupalRetrieverInterface service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, DOUserInfoRetriever $doUserInfoRetriever, DrupalRetrieverInterface $retriever) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->doUserInfoRetriever = $doUserInfoRetriever;
    $this->contribRetriever = $retriever;
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
      $container->get('do_username.user_service'),
      $container->get('ct_drupal_retriever'),
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
   * Returns an empty array as issues are included
   * during Drupal.org CodeContributions retrieval.
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
    $uid = $userInformation->getId();
    return $this->contribRetriever->getCodeContributions($uid);
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
