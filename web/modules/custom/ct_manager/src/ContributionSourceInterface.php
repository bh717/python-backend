<?php

declare(strict_types=1);

namespace Drupal\ct_manager;

use Drupal\user\Entity\User;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\CodeContributionCollection;
use Drupal\ct_manager\Data\IssueCollection;

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
  public function isUserValid(User $user): bool;

  /**
   * Get issues from the total contribution data.
   */
  public function getUserIssues(User $user): IssueCollection;

  /**
   * Get comments from the total contribution data.
   */
  public function getUserCodeContributions(User $user): CodeContributionCollection;

  /**
   * Get message for notification.
   */
  public function getNotificationMessage(CodeContribution $contribution, User $user): string;

}
