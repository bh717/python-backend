<?php

namespace Drupal\ct_manager\Data;

use InvalidArgumentException;

/**
 * Get an array of CodeContribution objects.
 */
class IssueCollection extends \ArrayObject {

  /**
   * Collection constructor.
   *
   * @param array $issues
   *   A list of issues.
   */
  public function __construct(array $issues) {
    foreach ($issues as $issue) {
      if (!$issue instanceof Issue) {
        throw new InvalidArgumentException("Object must be of type Issue");
      }
    }
    parent::__construct($issues);
  }

}
