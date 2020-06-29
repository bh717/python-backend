<?php

namespace Drupal\Tests\ct_manager\Unit\Data;

use DateTimeImmutable;
use Drupal\ct_manager\Data\CodeContribution;
use Drupal\ct_manager\Data\Issue;
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

  public function testChainedCodeContribution() {
    $date = new DateTimeImmutable();
    $issue = new Issue("issueTitle", "issueURL");
    $contribution = (new CodeContribution("Title", "https://drupal.org/example", $date))
      ->setAccountUrl("https://drupal.org/user/1")
      ->setDescription("Description")
      ->setFilesCount(1)
      ->setIssue($issue)
      ->setPatchCount(1)
      ->setProject("Project")
      ->setProjectUrl("https://drupal.org/project/example")
      ->setStatus("Status")
      ->setTechnology("Technology");

    $this->assertSame("Title", $contribution->getTitle());
    $this->assertSame("https://drupal.org/example", $contribution->getUrl());
    $this->assertSame("https://drupal.org/user/1", $contribution->getAccountUrl());
    $this->assertSame("Description", $contribution->getDescription());
    $this->assertSame($date->format('c'), $contribution->getDate()->format('c'));
    $this->assertSame(1, $contribution->getFilesCount());
    $this->assertSame("issueTitle", $contribution->getIssue()->getTitle());
    $this->assertSame("issueURL", $contribution->getIssue()->getUrl());
    $this->assertSame(1, $contribution->getPatchCount());
    $this->assertSame("Project", $contribution->getProject());
    $this->assertSame("https://drupal.org/project/example", $contribution->getProjectUrl());
    $this->assertSame("Status", $contribution->getStatus());
    $this->assertSame("Technology", $contribution->getTechnology());
  }

}
