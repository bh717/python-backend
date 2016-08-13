<?php

namespace Drupal\contrib_tracker;

use Drupal\user\UserInterface;

interface ContributionManagerInterface {

  /**
   * Retrieve all comments by an user and store them.
   *
   * @param int $uid
   *   The drupal.org user id.
   * @param \Drupal\user\UserInterface $user
   *   The contributing user.
   */
  public function storeCommentsByDrupalOrgUser($uid, UserInterface $user);

}
