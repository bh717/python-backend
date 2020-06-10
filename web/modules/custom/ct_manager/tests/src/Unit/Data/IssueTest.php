<?php

namespace Drupal\Tests\ct_manager\Unit\Data;

use Drupal\ct_manager\Data\Issue;
use Drupal\Tests\UnitTestCase;

/**
 * @group ct_manager
 * @coversDefaultClass \Drupal\ct_manager\Data\Issue
 */
class IssueTest extends UnitTestCase {

  /**
   * Test simple issue creation.
   */
  public function testIssueCreation() {
    $issue = new Issue("Title", "https://drupal.org/example");
    $this->assertSame("Title", $issue->getTitle());
    $this->assertSame("https://drupal.org/example", $issue->getUrl());
    $issue->setDescription("Description");
    $this->assertSame("Description", $issue->getDescription());
  }

}
