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

    // Create content for all node types.
    foreach ($content_type as $value) {
      $node = $this->createNode([
        'title' => 'This is unpublished',
        'type' => $value,
      ]);
      $this->assertEquals('This is unpublished', $node->getTitle());
      $this->assertEquals($value, $node->getType());
    }
    // Check if nodes are created for all types.
    $this->assertCount(count($content_type), $this->cleanupEntities);
  }

}
