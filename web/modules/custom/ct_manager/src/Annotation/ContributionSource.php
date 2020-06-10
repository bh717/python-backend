<?php

declare(strict_types=1);

namespace Drupal\ct_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Contribution tracker annotation object.
 *
 * @see \Drupal\ct_manager\ContributionTrackerPluginManager
 * @see plugin_api
 *
 * Note that the "@ Annotation" line below is required and should be the last
 * line in the docblock. It's used for discovery of Annotation definitions.
 *
 * @Annotation
 */
class ContributionSource extends Plugin {

  /**
   * A brief, human readable, description of the ContributionTracker type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
