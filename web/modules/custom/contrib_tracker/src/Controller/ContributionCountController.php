<?php

namespace Drupal\contrib_tracker\Controller;

use Drupal\contrib_tracker\ContributionStatistics;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A controller to display contribution statistics.
 */
class ContributionCountController extends ControllerBase {

  /**
   * Contribution statistics instance.
   *
   * @var \Drupal\contrib_tracker\ContributionStatistics
   */
  protected $contribStats;

  /**
   * Constructs a new GeofieldMapGeocoder object.
   *
   * @param \Drupal\contrib_tracker\ContributionStatistics $contrib_stats
   */
  public function __construct(ContributionStatistics $contrib_stats) {
    $this->contribStats = $contrib_stats;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('contrib_tracker.statistics')
    );
  }

  /**
   * Returns a render-able array with contribution statistics.
   */
  public function content() {
    $build = [
      '#theme' => 'contrib_tracker_contribution_count',
      '#totalContribution' => $this->contribStats->totalContributions(),
      '#contributionWithPatches' => $this->contribStats->contributionWithPatches(),
      '#totalPatches' => $this->contribStats->totalPatches(),
    ];
    return $build;
  }

}
