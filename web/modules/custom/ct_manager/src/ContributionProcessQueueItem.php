<?php

namespace Drupal\ct_manager;

use Drupal\user\Entity\User;

/**
 * Creates a value object with user and plugin id.
 */
class ContributionProcessQueueItem {

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

}
