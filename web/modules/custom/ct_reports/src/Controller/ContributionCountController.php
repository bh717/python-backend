<?php

namespace Drupal\ct_reports\Controller;

use Drupal\ct_reports\ContributionStatistics;
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
      $container->get('ct_reports.statistics')
    );
  }

  /**
   * Returns a render-able array with contribution statistics.
   */
  public function content() {

    $build = [
      '#theme' => 'ct_reports_contribution_count',
      '#totalContributions' => $this->contribStats->totalContributions(),
      '#codeContributions' => $this->contribStats->codeContributions(),
      '#totalContributors' => $this->contribStats->totalContributors(),
    ];
    return $build;
  }

}
