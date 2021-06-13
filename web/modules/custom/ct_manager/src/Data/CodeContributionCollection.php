<?php

namespace Drupal\ct_manager\Data;

use InvalidArgumentException;

/**
 * Get an array of CodeContribution objects.
 */
class CodeContributionCollection extends \ArrayObject {

  /**
   * Collection constructor.
   *
   * @param array $codeContributions
   *   A list of contributions.
   */
  public function __construct(array $codeContributions) {
    foreach ($codeContributions as $contribution) {
      if (!$contribution instanceof CodeContribution) {
        throw new InvalidArgumentException("Object must be of type CodeContribution");
      }
    }
    parent::__construct($codeContributions);
  }

}
