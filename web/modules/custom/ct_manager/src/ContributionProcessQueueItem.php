<?php

namespace Drupal\ct_manager;

use Drupal\user\Entity\User;

/**
 * Creates a value object with user and plugin id.
 */
class ContributionProcessQueueItem {

  /**
   * @var string Plugin ID.
   */
  protected string $plugin_id;

  /**
   * @var \Drupal\user\Entity\User User to be processed.
   */
  protected User $user;

  /**
   * User value object constructor.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\user\Entity\User $user
   *   The user object.
   */
  public function __construct($plugin_id, User $user) {
    $this->plugin_id = $plugin_id;
    $this->user = $user;
  }

  public function getPluginId(): string {
    return $this->plugin_id;
  }

  public function getUser(): User {
    return $this->user;
  }

}
