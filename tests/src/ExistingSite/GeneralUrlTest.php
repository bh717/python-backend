<?php

namespace tests\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * General tests for urls throughout the site.
 */
class GeneralUrlTest extends ExistingSiteBase {

  /**
   * Testing all of the key pages.
   */
  public function testKeyPages() {

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('user/login/google');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('all-contributions');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('code-contributions');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('event-contributions');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('non-code-contributions');
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalGet('contribution-count');
    $this->assertSession()->statusCodeEquals('200');

  }

}
