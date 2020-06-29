<?php

namespace Drupal\Tests\ct_manager\Unit\Data;

use DateTimeImmutable;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\IssueCollection;
use Drupal\ct_manager\Data\Issue;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @group ct_manager
 * @coversDefaultClass \Drupal\ct_manager\Data\IssueCollection
 */
class IssueCollectionTest extends UnitTestCase {

  /**
   * Test issue collection for sample inputs.
   */
  public function testIssueCollection() {
    $date = new DateTimeImmutable();
    $issues = [];
    $issues[] = new issue("Title", "https://drupal.org/example");
    $this->assertInstanceOf(IssueCollection::class, new IssueCollection($issues));
    unset($issues);
    $issues[] = new CodeContribution("Title", "https://drupal.org/example", $date);
    $this->expectException(InvalidArgumentException::class);
    new IssueCollection($issues);
    unset($issues);
    $issues[] = "test string";
    $this->expectException(InvalidArgumentException::class);
    new IssueCollection($issues);
  }

}
