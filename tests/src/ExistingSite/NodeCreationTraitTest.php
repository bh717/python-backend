<?php

namespace tests\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test the node creation trait.
 */
class NodeCreationTraitTest extends ExistingSiteBase {

  /**
   * Test if a node is created for a specific content type.
   */
  public function testNodeCreation() {
    $content_type = ['code_contribution', 'event', 'event_contribution',
      'issue', 'non_code_contribution',
    ];
    foreach ($content_type as $value) {
      $node = $this->createNode([
        'title' => 'This is unpublished',
        'type' => $value,
      ]);
      $this->assertContains('This is unpublished', $node->getTitle());
    }
  }

}
