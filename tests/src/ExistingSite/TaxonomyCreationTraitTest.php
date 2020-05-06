<?php

namespace tests\Entity;

use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test vocabulary and term creation traits.
 */
class TaxonomyCreationTraitTest extends ExistingSiteBase {

  /**
   * Test vocabulary can be created.
   */
  public function testVocabularyCreation() {
    $vocabulary = $this->createVocabulary();
    $this->assertCount(1, $this->cleanupEntities);
    $this->assertEquals($vocabulary->id(), $this->cleanupEntities[0]->id());
  }

  /**
   * Test if terms can be created for existing Vocabularies.
   */
  public function testTermCreation() {

    // Load all vocabulary items.
    $vocabulary = Vocabulary::loadMultiple(NULL);
    $i = 0;
    // Iterate over vocabulary items to create terms.
    foreach ($vocabulary as $value) {
      $term = $this->createTerm($value);
      $this->assertEquals($term->id(), $this->cleanupEntities[$i]->id());
      ++$i;
    }
    // Check if terms are created for all vocabularies.
    $this->assertCount(count($vocabulary), $this->cleanupEntities);
  }

}
