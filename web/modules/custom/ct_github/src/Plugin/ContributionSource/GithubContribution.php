<?php

namespace Drupal\ct_github\Plugin\ContributionSource;

use Drupal\ct_github\GithubQuery;
use Drupal\ct_github\GithubRetriever;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ct_manager\ContributionSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Retrieve and Store contributions from github.com.
 *
 * @ContributionSource(
 *   id = "github",
 *   description = @Translation("Retrieve and store contribution data from github."),
 * )
 */
class GithubContribution extends PluginBase implements ContributionSourceInterface, ContainerFactoryPluginInterface {

  /**
   * User Entity Storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User Entity Storage.
   *
   * @var \Drupal\ct_github\GithubRetriever
   */
  protected $retriever = [];

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
   * @param \Drupal\ct_github\GithubQuery $query
   *   The injected github query service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, GithubQuery $query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->query = $query;
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
      $container->get('ct_github.query')
    );
  }

  /**
   * Get all users with github username.
   */
  public function getUsers() {
    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('field_github_username', '', '!=')
      ->condition('status', 1)
      ->execute();
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
    return $users;
  }

  /**
   * Get user information to validate the username.
   */
  public function isUserValid($user) {
    $username = $user->field_github_username[0]->getValue()['value'];
    return $this->query->isUserValid($username);
  }

  /**
   * Returns a user retriever object.
   */
  protected function getOrCreateRetriever($user) {
    $username = $user->field_github_username[0]->getValue()['value'];
    if (isset($this->retriever[$username])) {
      return $this->retriever[$username];
    }
    $this->retriever[$username] = new GithubRetriever($this->query, $username);
    return $this->retriever[$username];
  }

  /**
   * Get issues and pull requests associated with a user.
   */
  public function getIssues($user) {
    return $this->getOrCreateRetriever($user)->getIssues();
  }

  /**
   * Get issue comments and PR commits associated with a user.
   */
  public function getCodeContributions($user) {
    return $this->getOrCreateRetriever($user)->getCodeContributions();
  }

}
