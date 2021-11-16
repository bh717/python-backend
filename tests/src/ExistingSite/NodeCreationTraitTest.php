<?php

namespace tests\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\Component\Serialization\Json;

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

  /**
   * Test non-code contribution workflow.
   */
  public function testNonCodeContribCreation() {

    // Create a new user and make it current user.
    $this->setUpCurrentUser();

    // Create a non_code_contribution node.
    $non_code_contribution_node = $this->createNode([
      'title' => 'Test node',
      'type' => 'non_code_contribution',
      'field_non_code_contribution_type' => 'blog',
    ]);
    $non_code_contribution_node->setPublished()->save();

    $this->assertEquals('Test node', $non_code_contribution_node->getTitle());
    $this->assertEquals('non_code_contribution', $non_code_contribution_node->getType());

    // Check if the node appears on relevant views.
    $response = $this->drupalGet('/non-code-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test node');

    $response = $this->drupalGet('/all-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test node');

    $response = $this->drupalGet('/api/views/all-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('Test node', Json::decode($response)[0]['title']);
  }

  /**
   * Test event contribution workflow.
   */
  public function testEventContribCreation() {

    // Create a new user and make it current user.
    $this->setUpCurrentUser();

    // Create a non_code_contribution node.
    $event_contribution_node = $this->createNode([
      'title' => 'Test event node',
      'type' => 'event_contribution',
      // Used in views with default sort as DESC of field_contribution_date
      // to show the node at the top of results.
      'field_contribution_date' => date('Y-m-d', time()),
    ]);
    $event_contribution_node->setPublished()->save();

    $this->assertEquals('Test event node', $event_contribution_node->getTitle());
    $this->assertEquals('event_contribution', $event_contribution_node->getType());

    $response = $this->drupalGet('/event-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test event node');

    $response = $this->drupalGet('/all-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test event node');

    $response = $this->drupalGet('/api/views/all-contributions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('Test event node', Json::decode($response)[0]['title']);
  }

}
