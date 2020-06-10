<?php

namespace Drupal\Tests\ct_manager\Unit\Data;

use DateTimeImmutable;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\Tests\UnitTestCase;

/**
 * @group ct_manager
 * @coversDefaultClass \Drupal\ct_manager\Data\CodeContribution
 */
class CodeContributionTest extends UnitTestCase {

  /**
   * Test simple code contribution creation.
   */
  public function testCodeContributionCreation() {
    $date = new DateTimeImmutable();
    $contribution = new CodeContribution("Title", "https://drupal.org/example", $date);
    $this->assertSame("Title", $contribution->getTitle());
    $this->assertSame("https://drupal.org/example", $contribution->getUrl());
    $this->assertSame($date->format('c'), $contribution->getDate()->format('c'));
    $contribution->setDescription("Description");
    $this->assertSame("Description", $contribution->getDescription());
  }

}
