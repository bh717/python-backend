<?php

namespace tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test if the view exists in the home page.
 */
class HomePageViewDisplayTest extends ExistingSiteBase {

  /**
   * Function to test if the view exists.
   */
  public function testViewExists() {
    $this->drupalGet('all-contributions');
    $this->assertSession()->statusCodeEquals('200');
  }

}
