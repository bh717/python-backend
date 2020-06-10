<?php

namespace Drupal\ct_manager;

/**
 * An interface for all Contribution type plugins.
 */
interface ContributionSourceInterface {

  /**
   * Get users which can be processed by this plugin.
   */
  public function getUsers();

  /**
   * Get user contributions from the platform.
   */
  public function getUserInformation($user);

  /**
   * Get issues from the total contribution data.
   */
  public function getUserIssues($user);

  /**
   * Get comments from the total contribution data.
   */
  public function getUserComments($user);

}
