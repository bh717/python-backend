<?php

namespace Drupal\contrib_tracker;

interface UserResolverInterface {

  /**
   * Get user information from drupal.org.
   *
   * @param string $username
   *   The drupal.org username.
   *
   * @return \Hussainweb\DrupalApi\Entity\User
   *   User information returned from drupal.org API.
   */
  public function getUserInformation($username);

}
