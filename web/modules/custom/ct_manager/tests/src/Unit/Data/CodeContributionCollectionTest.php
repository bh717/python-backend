<?php

namespace Drupal\Tests\ct_manager\Unit\Data;

use DateTimeImmutable;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\CodeContributionCollection;
use Drupal\ct_manager\Data\Issue;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @group ct_manager
 * @coversDefaultClass \Drupal\ct_manager\Data\CodeContributionCollection
 */
class CodeContributionCollectionTest extends UnitTestCase {

  /**
   * Test code contribution collection for sample inputs.
   */
  public function testCodeContributionCollection() {
    $date = new DateTimeImmutable();
    $contributions = [];
    $contributions[] = new CodeContribution("Title", "https://drupal.org/example", $date);
    $this->assertInstanceOf(CodeContributionCollection::class, new CodeContributionCollection($contributions));
    unset($contributions);
    $contributions[] = new issue("Title", "https://drupal.org/example");
    $this->expectException(InvalidArgumentException::class);
    new CodeContributionCollection($contributions);
    unset($contributions);
    $contributions[] = "test string";
    $this->expectException(InvalidArgumentException::class);
    new CodeContributionCollection($contributions);
  }

}
