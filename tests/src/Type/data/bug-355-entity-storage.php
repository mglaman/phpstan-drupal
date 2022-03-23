<?php

namespace DrupalEntity;

use Drupal\node\NodeStorageInterface;
use function PHPStan\Testing\assertType;

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
if ($nodeStorage instanceof NodeStorageInterface) {
    assertType('Drupal\node\Entity\Node', $nodeStorage->create(['type' => 'page', 'title' => 'foo']));
    assertType('Drupal\node\Entity\Node|null', $nodeStorage->load(42));
    assertType('array<int, Drupal\node\Entity\Node>', $nodeStorage->loadMultiple([42, 29]));
}

/** @var \Drupal\node\NodeStorage $typedNodeStorage */
$typedNodeStorage = \Drupal::entityTypeManager()->getStorage('node');
assertType('Drupal\node\Entity\Node', $typedNodeStorage->create(['type' => 'page', 'title' => 'foo']));
assertType('Drupal\node\Entity\Node|null', $typedNodeStorage->load(42));
assertType('array<int, Drupal\node\Entity\Node>', $typedNodeStorage->loadMultiple([42, 29]));

/** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
$termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
assertType('Drupal\taxonomy\Entity\Term', $termStorage->create(['vid' => 'tag', 'name' => 'foo']));
assertType('Drupal\taxonomy\Entity\Term|null', $termStorage->load('42'));
assertType('array<int, Drupal\taxonomy\Entity\Term>', $termStorage->loadMultiple([42, 29]));
